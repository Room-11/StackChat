<?php declare(strict_types = 1);

namespace Room11\StackChat\Room;

use Room11\StackChat\Auth\Session;
use Room11\StackChat\WebSocket\Handler as WebSocketHandler;

class Room
{
    private $identifier;
    private $session;
    private $permanent;
    private $websocketHandler;

    public function __construct(
        Identifier $identifier,
        Session $sessionInfo,
        WebSocketHandler $websocketHandler,
        bool $permanent
    ) {
        $this->identifier = $identifier;
        $this->session = $sessionInfo;
        $this->websocketHandler = $websocketHandler;
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

    public function __debugInfo()
    {
        return [
            'identifier' => $this->identifier,
            'sessionInfo' => $this->session,
            'websocketEndpoint' => $this->websocketHandler->getEndpoint()->getInfo(),
        ];
    }
}
