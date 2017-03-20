<?php declare(strict_types = 1);

namespace Room11\StackChat\Room;

use Amp\Artax\HttpClient;
use Amp\Artax\Response as HttpResponse;
use Amp\Promise;
use Room11\DOMUtils\ElementNotFoundException;
use Room11\StackChat\Auth\ActiveSessionTracker;
use Room11\StackChat\Endpoint;
use Room11\StackChat\EndpointURLResolver;

class ChatRoomAclDataAccessor implements AclDataAccessor
{
    private static $usercardQuery;
    private static $usernameQuery;

    private $httpClient;
    private $urlResolver;
    private $sessions;

    public function __construct(HttpClient $httpClient, EndpointURLResolver $urlResolver, ActiveSessionTracker $sessions)
    {
        $this->httpClient = $httpClient;
        $this->urlResolver = $urlResolver;
        $this->sessions = $sessions;

        self::$usercardQuery = './/div[' . \Room11\DOMUtils\xpath_html_class('usercard') . ']';
        self::$usernameQuery = './/a[' . \Room11\DOMUtils\xpath_html_class('username') . ']';
    }

    private function parseRoomAccessSection(\DOMElement $section): array
    {
        try {
            $userEls = \Room11\DOMUtils\xpath_get_elements($section, self::$usercardQuery);
        } catch (ElementNotFoundException $e) {
            return [];
        }

        $users = [];

        foreach ($userEls as $userEl) {
            $profileAnchor = \Room11\DOMUtils\xpath_get_element($userEl, self::$usernameQuery);

            if (!preg_match('#^/users/([0-9]+)/#', $profileAnchor->getAttribute('href'), $match)) {
                continue;
            }

            $users[(int)$match[1]] = trim($profileAnchor->textContent);
        }

        return $users;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoomAccess(Room $room): Promise
    {
        $url = $this->urlResolver->getEndpointURL($room, Endpoint::CHATROOM_INFO_ACCESS);

        return \Amp\resolve(function() use($url) {
            /** @var HttpResponse $response */
            $response = yield $this->httpClient->request($url);

            $doc = \Room11\DOMUtils\domdocument_load_html($response->getBody());

            $result = [];

            foreach ([UserAccessType::READ_ONLY, UserAccessType::READ_WRITE, UserAccessType::OWNER] as $accessType) {
                $sectionEl = $doc->getElementById('access-section-' . $accessType);
                $result[$accessType] = $sectionEl !== null ? $this->parseRoomAccessSection($sectionEl) : [];
            }

            return $result;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getRoomOwners(Room $room): Promise
    {
        return \Amp\resolve(function() use($room) {
            $users = yield $this->getRoomAccess($room);
            return $users[UserAccessType::OWNER];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function isRoomOwner(Room $room, int $userId): Promise
    {
        return \Amp\resolve(function() use($room, $userId) {
            $users = yield $this->getRoomOwners($room);
            return isset($users[$userId]);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthenticatedUserRoomOwner(Room $room): Promise
    {
        return $this->isRoomOwner($room, $this->sessions->getSessionForRoom($room)->getUser()->getId());
    }
}
