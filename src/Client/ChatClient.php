<?php declare(strict_types = 1);

namespace Room11\StackChat\Client;

use Amp\Artax\FormBody;
use Amp\Artax\HttpClient;
use Amp\Artax\Request as HttpRequest;
use Amp\Artax\Response as HttpResponse;
use Amp\Promise;
use Psr\Log\LoggerInterface as Logger;
use Room11\DOMUtils\ElementNotFoundException;
use Room11\StackChat\Auth\ActiveSessionTracker;
use Room11\StackChat\Client\Actions\Factory as ActionFactory;
use Room11\StackChat\Endpoint;
use Room11\StackChat\EndpointURLResolver;
use Room11\StackChat\Entities\ChatUser;
use Room11\StackChat\Entities\MainSiteUser;
use Room11\StackChat\Message;
use Room11\StackChat\Room\Identifier as RoomIdentifier;
use Room11\StackChat\Room\IdentifierFactory as RoomIdentifierFactory;
use Room11\StackChat\Room\PostNotPermittedException;
use Room11\StackChat\Room\PostPermissionManager;
use Room11\StackChat\Room\Room;
use function Amp\all;
use function Amp\resolve;
use function Room11\DOMUtils\domdocument_load_html;
use function Room11\DOMUtils\xpath_get_elements;

class ChatClient implements Client
{
    private $textFormatter;
    private $httpClient;
    private $logger;
    private $actionExecutor;
    private $actionFactory;
    private $urlResolver;
    private $identifierFactory;
    private $postPermissionManager;
    private $sessions;

    public function __construct(
        TextFormatter $textFormatter,
        HttpClient $httpClient,
        Logger $logger,
        ActionExecutor $actionExecutor,
        ActionFactory $actionFactory,
        EndpointURLResolver $urlResolver,
        RoomIdentifierFactory $identifierFactory,
        PostPermissionManager $postPermissionManager,
        ActiveSessionTracker $sessions
    ) {
        $this->textFormatter = $textFormatter;
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->actionExecutor = $actionExecutor;
        $this->actionFactory = $actionFactory;
        $this->urlResolver = $urlResolver;
        $this->identifierFactory = $identifierFactory;
        $this->postPermissionManager = $postPermissionManager;
        $this->sessions = $sessions;
    }

    private function applyPostFlagsToText(string $text, int $flags): string
    {
        $text = rtrim($this->textFormatter->checkAndNormalizeEncoding($text));

        if ($flags & PostFlags::SINGLE_LINE) {
            $text = preg_replace('#\s+#u', ' ', $text);
        }
        if ($flags & PostFlags::FIXED_FONT) {
            $text = preg_replace('#(^|\r?\n)#', '$1    ', $text);
        }
        if (!($flags & PostFlags::ALLOW_PINGS)) {
            $text = $this->textFormatter->stripPingsFromText($text);
        }
        if (!($flags & PostFlags::ALLOW_REPLIES)) {
            $text = preg_replace('#^:([0-9]+)\s*#', '', $text);
        }
        if (($flags & ~PostFlags::SINGLE_LINE) & PostFlags::TRUNCATE) {
            $text = $this->textFormatter->truncateText($text);
        }

        return $text;
    }

    private function getIdentifierFromArg($arg): RoomIdentifier
    {
        if ($arg instanceof Room) {
            return $arg->getIdentifier();
        } else if ($arg instanceof RoomIdentifier) {
            return $arg;
        }

        throw new \InvalidArgumentException('Invalid chat room identifier');
    }

    /**
     * @param Message|int $messageOrId
     * @param Room|null $room
     * @return array
     */
    private function getMessageIDAndRoomPairFromArgs($messageOrId, Room $room = null): array
    {
        if ($messageOrId instanceof Message) {
            return [$messageOrId->getId(), $messageOrId->getRoom()];
        }

        if (!is_int($messageOrId)) {
            throw new \InvalidArgumentException('$messageOrId must be integer or instance of ' . Message::class);
        }

        if ($room === null) {
            throw new \InvalidArgumentException('Room required if message ID is specified');
        }

        return [$messageOrId, $room];
    }

    /**
     * @param Room|RoomIdentifier $room
     * @param int $messageId
     * @return Promise<Room>
     */
    public function getRoomIdentifierFromMessageID($room, int $messageId)
    {
        $identifier = $this->getIdentifierFromArg($room);
        $url = $this->urlResolver->getEndpointURL($identifier, Endpoint::CHATROOM_GET_MESSAGE_HISTORY, $messageId);

        return resolve(function() use($identifier, $url, $messageId) {
            /** @var HttpResponse $response */
            $response = yield $this->httpClient->request($url);

            $doc = domdocument_load_html($response->getBody());
            $els = (new \DOMXPath($doc))->query("//*[@id='message-{$messageId}']//a[@name='{$messageId}']");

            if ($els->length === 0) {
                throw new DataFetchFailureException('Unable to find message anchor element in response HTML');
            }

            /** @var \DOMElement $anchorEl */
            $anchorEl = $els->item(0);

            if (!preg_match('~^/transcript/([0-9]+)~', $anchorEl->getAttribute('href'), $match)) {
                throw new DataFetchFailureException('Message anchor element href in an unexpected format');
            }

            return $this->identifierFactory->create((int)$match[1], $identifier->getHost());
        });
    }

    /**
     * @param Room|RoomIdentifier $room
     * @param int[] ...$ids
     * @return Promise
     */
    public function getChatUsers($room, int ...$ids): Promise
    {
        $identifier = $this->getIdentifierFromArg($room);
        $url = $this->urlResolver->getEndpointURL($room, Endpoint::CHAT_USER_INFO);

        $body = (new FormBody)
            ->addField('roomId', $identifier->getId())
            ->addField('ids', implode(',', $ids));

        $request = (new HttpRequest)
            ->setMethod('POST')
            ->setUri($url)
            ->setBody($body);

        return resolve(function() use($request, $identifier) {
            /** @var HttpResponse $response */
            $response = yield $this->httpClient->request($request);

            return array_map(function($data) use($identifier) {
                return new ChatUser($data);
            }, json_try_decode($response->getBody(), true)['users'] ?? []);
        });
    }

    /**
     * @param Room|RoomIdentifier $room
     * @param int[] ...$ids
     * @return Promise
     */
    public function getMainSiteUsers($room, int ...$ids): Promise
    {
        $identifier = $this->getIdentifierFromArg($room);

        $promises = [];

        foreach ($ids as $id) {
            $url = $this->urlResolver->getEndpointURL($room, Endpoint::MAINSITE_USER, $id);
            $promises[$id] = $this->httpClient->request($url);
        }

        return resolve(function() use($promises, $identifier) {
            /** @var HttpResponse[] $responses */
            $responses = yield all($promises);

            $result = [];

            foreach ($responses as $id => $response) {
                $result[$id] = MainSiteUser::createFromDOMDocument(domdocument_load_html($response->getBody()));
            }

            return $result;
        });
    }

    /**
     * @param Room|RoomIdentifier $room
     * @return Promise
     */
    public function getPingableUsers($room): Promise
    {
        $url = $this->urlResolver->getEndpointURL($room, Endpoint::CHATROOM_INFO_PINGABLE);

        return resolve(function() use($url) {
            /** @var HttpResponse $response */
            $response = yield $this->httpClient->request($url);

            if ($response->getStatus() !== 200) {
                throw new DataFetchFailureException(
                    "Fetching pingable users list failed with response code " . $response->getStatus()
                );
            }

            $result = [];

            foreach (json_try_decode($response->getBody(), true) as $item) {
                $result[] = [
                    'id'       => (int)$item[0],
                    'name'     => $item[1],
                    'pingable' => preg_replace('~\s+~', '', $item[1]),
                ];
            }

            return $result;
        });
    }

    /**
     * @param Room|RoomIdentifier $room
     * @param string $name
     * @return Promise
     */
    public function getPingableName($room, string $name): Promise
    {
        return resolve(function() use($room, $name) {
            $lower = strtolower($name);
            $users = yield $this->getPingableUsers($room);

            foreach ($users as $user) {
                if (strtolower($user['name']) === $lower || strtolower($user['pingable']) === $lower) {
                    return $user['pingable'];
                }
            }

            return null;
        });
    }

    /**
     * @param Room|RoomIdentifier $room
     * @param string[] $names
     * @return Promise<int[]>
     */
    public function getPingableUserIDs($room, string ...$names): Promise
    {
        return resolve(function() use($room, $names) {
            $users = yield $this->getPingableUsers($room);
            $result = [];

            foreach ($names as $name) {
                $lower = strtolower($name);

                foreach ($users as $user) {
                    if (strtolower($user['name']) === $lower || strtolower($user['pingable']) === $lower) {
                        $result[$name] = $user['id'];
                        break;
                    }
                }
            }

            return $result;
        });
    }

    /**
     * @param Room|RoomIdentifier $room
     * @return Promise
     */
    public function getPinnedMessages($room): Promise
    {
        $url = $this->urlResolver->getEndpointURL($room, Endpoint::CHATROOM_STARS_LIST);

        return resolve(function() use($url) {
            /** @var HttpResponse $response */
            $this->logger->debug('Getting pinned messages');
            $response = yield $this->httpClient->request($url);

            $doc = domdocument_load_html($response->getBody());

            try {
                $pinnedEls = xpath_get_elements($doc, ".//li[./span[contains(concat(' ', normalize-space(@class), ' '), ' owner-star ')]]");
            } catch (ElementNotFoundException $e) {
                return [];
            }

            $result = [];
            foreach ($pinnedEls as $el) {
                $result[] = (int)explode('_', $el->getAttribute('id'))[1];
            }

            $this->logger->debug('Got pinned messages: ' . implode(',', $result));
            return $result;
        });
    }

    /**
     * @param Room|RoomIdentifier $room
     * @param int $id
     * @return Promise
     */
    public function getMessageHTML($room, int $id): Promise
    {
        $url = $this->urlResolver->getEndpointURL($room, Endpoint::CHATROOM_GET_MESSAGE_HTML, $id);

        return resolve(function() use($url, $id) {
            /** @var HttpResponse $response */
            $response = yield $this->httpClient->request($url);

            if ($response->getStatus() !== 200) {
                throw new MessageFetchFailureException(
                    "Fetching message #{$id} failed with response code " . $response->getStatus()
                );
            }

            return (string)$response->getBody();
        });
    }

    /**
     * @param Room|RoomIdentifier $room
     * @param int $id
     * @return Promise
     */
    public function getMessageText($room, int $id): Promise
    {
        $url = $this->urlResolver->getEndpointURL($room, Endpoint::CHATROOM_GET_MESSAGE_TEXT, $id);

        return resolve(function() use($url, $id) {
            /** @var HttpResponse $response */
            $response = yield $this->httpClient->request($url);

            if ($response->getStatus() !== 200) {
                throw new MessageFetchFailureException(
                    "Fetching message #{$id} failed with response code " . $response->getStatus()
                );
            }

            return (string)$response->getBody();
        });
    }

    /**
     * @param Room|RoomContainer $target
     * @param string $text
     * @param int $flags
     * @return Promise
     */
    public function postMessage($target, string $text, int $flags = PostFlags::NONE): Promise
    {
        return resolve(function() use ($target, $text, $flags) {
            if ($target instanceof Room) {
                $room = $target;
            } else if ($target instanceof RoomContainer) {
                $room = $target->getRoom();
            } else {
                throw new InvalidMessageTargetException(
                    'Message target must be an instance of ' . Room::class . ' or ' . RoomContainer::class
                );
            }

            $parentMessage = $target instanceof Message
                ? $target
                : null;

            if (!($flags & PostFlags::FORCE) && !(yield $this->postPermissionManager->isPostAllowed($room->getIdentifier()))) {
                throw new PostNotPermittedException('Not approved for message posting in this room');
            }

            try {
                $text = $this->applyPostFlagsToText($text, $flags);
            } catch (TextFormatException $e) {
                throw new MessagePostFailureException($e->getMessage(), $e->getCode(), $e);
            }

            $body = (new FormBody)
                ->addField("text", $text)
                ->addField("fkey", (string)$this->sessions->getSessionForRoom($room->getIdentifier())->getFKey());

            $url = $this->urlResolver->getEndpointURL($room, Endpoint::CHATROOM_POST_MESSAGE);

            $request = (new HttpRequest)
                ->setUri($url)
                ->setMethod("POST")
                ->setBody($body);

            $action = $this->actionFactory->createPostMessageAction($request, $room, $text, $parentMessage);
            $this->actionExecutor->enqueue($action);

            return $action->promise();
        });
    }

    public function moveMessages(Room $room, int $targetRoomId, int ...$messageIds): Promise
    {
        $body = (new FormBody)
            ->addField("fkey", $this->sessions->getSessionForRoom($room->getIdentifier())->getFKey())
            ->addField('ids', implode(',', $messageIds))
            ->addField('to', $targetRoomId);

        $url = $this->urlResolver->getEndpointURL($room, Endpoint::CHATROOM_MOVE_MESSAGE);

        $request = (new HttpRequest)
            ->setUri($url)
            ->setMethod("POST")
            ->setBody($body);

        $action = $this->actionFactory->createMoveMessageAction($request, $room);
        return $this->actionExecutor->enqueue($action);
    }

    /**
     * @param Message $origin
     * @param string $text
     * @param int $flags
     * @return Promise
     * @internal param string $text
     */
    public function postReply(Message $origin, string $text, int $flags = PostFlags::NONE): Promise
    {
        $flags |= PostFlags::ALLOW_REPLIES;
        $flags &= ~PostFlags::FIXED_FONT;

        return $this->postMessage($origin, ":{$origin->getId()} {$text}", $flags);
    }

    /**
     * @param Message $message
     * @param string $text
     * @param int $flags
     * @return Promise
     * @throws MessagePostFailureException
     */
    public function editMessage(Message $message, string $text, int $flags = PostFlags::NONE): Promise
    {
        try {
            $text = $this->applyPostFlagsToText($text, $flags);
        } catch (TextFormatException $e) {
            throw new MessagePostFailureException($e->getMessage(), $e->getCode(), $e);
        }

        $body = (new FormBody)
            ->addField("text", $text)
            ->addField("fkey", (string)$this->sessions->getSessionForRoom($message->getRoom()->getIdentifier())->getFKey());

        $url = $this->urlResolver->getEndpointURL($message->getRoom(), Endpoint::CHATROOM_EDIT_MESSAGE, $message->getId());

        $request = (new HttpRequest)
            ->setUri($url)
            ->setMethod("POST")
            ->setBody($body);

        $action = $this->actionFactory->createEditMessageAction($request, $message->getRoom());

        return $this->actionExecutor->enqueue($action);
    }

    /**
     * @param Message|int $messageOrId
     * @param Room|null $room
     * @return Promise
     */
    public function pinOrUnpinMessage($messageOrId, Room $room = null): Promise
    {
        list($messageId, $room) = $this->getMessageIDAndRoomPairFromArgs($messageOrId, $room);

        $body = (new FormBody)
            ->addField("fkey", $room->getSession()->getFKey());

        $url = $this->urlResolver->getEndpointURL($room, Endpoint::CHATROOM_PIN_MESSAGE, $messageId);

        $request = (new HttpRequest)
            ->setUri($url)
            ->setMethod("POST")
            ->setBody($body);

        $action = $this->actionFactory->createPinOrUnpinMessageAction($request, $room);

        return $this->actionExecutor->enqueue($action);
    }

    /**
     * @param Message|int $messageOrId
     * @param Room|null $room
     * @return Promise
     */
    public function unstarMessage($messageOrId, Room $room = null): Promise
    {
        list($messageId, $room) = $this->getMessageIDAndRoomPairFromArgs($messageOrId, $room);

        $body = (new FormBody)
            ->addField("fkey", $room->getSession()->getFKey());

        $url = $this->urlResolver->getEndpointURL($room, Endpoint::CHATROOM_UNSTAR_MESSAGE, $messageId);

        $request = (new HttpRequest)
            ->setUri($url)
            ->setMethod("POST")
            ->setBody($body);

        $action = $this->actionFactory->createUnstarMessageAction($request, $room);

        return $this->actionExecutor->enqueue($action);
    }
}
