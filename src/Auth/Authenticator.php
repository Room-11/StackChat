<?php declare(strict_types=1);

namespace Room11\StackChat\Auth;

use Amp\Artax\FormBody;
use Amp\Artax\HttpClient;
use Amp\Artax\Request as HttpRequest;
use Amp\Artax\Response as HttpResponse;
use Amp\Deferred;
use Amp\Promise;
use Ds\Queue;
use Room11\OpenId\Authenticator as OpenIdAuthenticator;
use Room11\OpenId\Credentials;
use Room11\StackChat\Client\Client;
use Room11\StackChat\Endpoint;
use Room11\StackChat\EndpointURLResolver;
use Room11\StackChat\Room\Room;
use function Amp\all;
use function Amp\resolve;
use function Room11\DOMUtils\domdocument_load_html;

class Authenticator
{
    private $httpClient;
    private $sessionInfoFactory;
    private $authenticator;
    private $credentialManager;
    private $chatClient;
    private $urlResolver;

    private $haveLoop = false;
    private $queue;

    public function __construct(
        HttpClient $httpClient,
        Client $chatClient,
        SessionFactory $sessionInfoFactory,
        OpenIdAuthenticator $authenticator,
        CredentialManager $credentialManager,
        EndpointURLResolver $urlResolver
    ) {
        $this->httpClient = $httpClient;
        $this->chatClient = $chatClient;
        $this->sessionInfoFactory = $sessionInfoFactory;
        $this->authenticator = $authenticator;
        $this->credentialManager = $credentialManager;
        $this->urlResolver = $urlResolver;

        $this->queue = new Queue;
    }

    public function getRoomSessionInfo(Room $room): Promise
    {
        $deferred = new Deferred;
        $this->queue->push([$room, $deferred]);

        if (!$this->haveLoop) {
            /** @noinspection PhpVoidFunctionResultUsedInspection */
            /** @noinspection PhpParamsInspection */
            resolve($this->executeActionsFromQueue());
        }

        return $deferred->promise();
    }

    private function executeActionsFromQueue()
    {
        $this->haveLoop = true;

        while ($this->queue->count() > 0) {
            /** @var Room $room */
            /** @var Deferred $deferred */
            list($room, $deferred) = $this->queue->pop();

            try {
                $deferred->succeed(yield from $this->getSessionInfoForRoom($room));
            } catch (\Throwable $e) {
                $deferred->fail($e);
            }
        }

        $this->haveLoop = false;
    }

    private function getSessionInfoForRoom(Room $room)
    {
        /** @var HttpResponse $response */
        $url = $this->urlResolver->getEndpointURL($room, Endpoint::CHATROOM_UI);
        $response = yield $this->httpClient->request($url);

        $doc = domdocument_load_html($response->getBody());
        $xpath = new \DOMXPath($doc);

        $mainSiteURL = $this->getMainSiteUrl($xpath);

        if (!$this->isLoggedInMainSite($doc)) {
            $credentials = $this->credentialManager->getCredentialsForDomain($room->getHost());
            $xpath = yield from $this->logInMainSite($doc, $credentials);
        }

        $fkey = $this->getFKey($xpath);
        $user = yield from $this->getUser($room, $xpath);

        $webSocketURL = yield from $this->getWebSocketUri($room, $fkey);

        return $this->sessionInfoFactory->build($user, $fkey, $mainSiteURL, $webSocketURL);
    }

    private function logInMainSite(\DOMDocument $doc, Credentials $credentials)
    {
        $url = $this->getLogInURL(new \DOMXPath($doc));

        /** @var HttpResponse $response */
        $response = yield from $this->authenticator->logIn($url, $credentials);

        $doc = domdocument_load_html($response->getBody());
        if (!$this->isLoggedInMainSite($doc)) {
            throw new \RuntimeException('Still not logged in'); //todo
        }

        return new \DOMXPath($doc);
    }

    private function isLoggedInMainSite(\DOMDocument $doc)
    {
        return $doc->getElementById('input') !== null;
    }

    private function getLogInURL(\DOMXPath $xpath): string
    {
        /** @var \DOMElement $node */

        $nodes = $xpath->query("//div[@id='bubble']/a[text()='logged in']");
        if ($nodes->length < 1) {
            throw new \RuntimeException('Could not get login URL node'); //todo
        }

        $node = $nodes->item(0);
        return $node->getAttribute('href');
    }

    private function getMainSiteUrl(\DOMXPath $xpath): string
    {
        /** @var \DOMElement $node */

        $nodes = $xpath->query("//td[@id='footer-logo']/a");
        if ($nodes->length < 1) {
            throw new \RuntimeException('Could not find URL for the main site for this chat room');
        }

        $node = $nodes->item(0);
        return $node->getAttribute('href');
    }

    private function getFKey(\DOMXPath $xpath): string
    {
        /** @var \DOMElement $node */

        $nodes = $xpath->query("//input[@name='fkey']");
        if ($nodes->length < 1) {
            throw new \RuntimeException('Could not find fkey for chat room');
        }

        $node = $nodes->item(0);
        return $node->getAttribute('value');
    }

    private function getUser(Room $room, \DOMXPath $xpath)
    {
        /** @var \DOMElement $node */

        $nodes = $xpath->query("//div[@id='active-user']");
        if ($nodes->length < 1) {
            throw new \RuntimeException('Could not find user ID for chat room: no active-user div');
        }

        $node = $nodes->item(0);
        if (!preg_match('#\buser-([0-9]+)\b#', $node->getAttribute('class'), $match)) {
            throw new \RuntimeException('Could not find user ID for chat room: no user ID class');
        }

        $user = yield $this->chatClient->getChatUsers($room, (int)$match[1]);

        return $user[0];
    }

    private function getWebSocketUri(Room $room, string $fKey)
    {
        $authBody = (new FormBody)
            ->addField("roomid", $room->getId())
            ->addField("fkey", $fKey);

        $historyBody = (new FormBody)
            ->addField('since', 0)
            ->addField('mode', 'Messages')
            ->addField("msgCount", 1)
            ->addField("fkey", $fKey);

        $requests = [
            'auth' => (new HttpRequest)
                ->setUri($this->urlResolver->getEndpointURL($room, Endpoint::CHATROOM_WEBSOCKET_AUTH))
                ->setMethod("POST")
                ->setBody($authBody),
            'history' => (new HttpRequest)
                ->setUri($this->urlResolver->getEndpointURL($room, Endpoint::CHATROOM_EVENT_HISTORY))
                ->setMethod("POST")
                ->setBody($historyBody),
        ];

        /** @var HttpResponse[] $responses */
        $responses = yield all($this->httpClient->requestMulti($requests));

        $authInfo = json_try_decode($responses['auth']->getBody(), true);
        $historyInfo = json_try_decode($responses['history']->getBody(), true);

        if (!isset($authInfo['url'])) {
            throw new \RuntimeException("WebSocket auth did not return URL");
        }
        if (!isset($historyInfo['time'])) {
            throw new \RuntimeException("Could not get time for WebSocket URL");
        }

        return $authInfo['url'] . '?l=' . $historyInfo['time'];
    }
}
