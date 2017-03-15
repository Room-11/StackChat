<?php declare(strict_types = 1);

namespace Room11\StackExchangeChatClient\Room;

use Room11\StackExchangeChatClient\Auth\Session;
use Room11\StackExchangeChatClient\WebSocket\Handler as WebSocketHandler;
use Room11\StackExchangeChatClient\Storage\KeyValue as KeyValueStore;

class Room
{
    private $identifier;
    private $session;
    private $presenceManager;
    private $permanent;
    private $websocketHandler;
    private $keyValueStore;

    public function __construct(
        Identifier $identifier,
        Session $sessionInfo,
        WebSocketHandler $websocketHandler,
        PresenceManager $presenceManager,
        KeyValueStore $keyValueStore,
        bool $permanent
    ) {
        $this->identifier = $identifier;
        $this->session = $sessionInfo;
        $this->websocketHandler = $websocketHandler;
        $this->keyValueStore = $keyValueStore;
        $this->presenceManager = $presenceManager;
        $this->permanent = $permanent;
    }

    public function getIdentifier(): Identifier
    {
        return $this->identifier;
    }

    public function getSession(): Session
    {
        return $this->session;
    }

    public function isPermanent(): bool
    {
        return $this->permanent;
    }

    public function getWebsocketHandler(): WebSocketHandler
    {
        return $this->websocketHandler;
    }

    public function getKeyValueStore(): KeyValueStore
    {
        return $this->keyValueStore;
    }

    public function __debugInfo()
    {
        return [
            'identifier' => $this->identifier,
            'sessionInfo' => $this->session,
            'websocketEndpoint' => $this->websocketHandler->getEndpoint()->getInfo(),
        ];
    }
}
