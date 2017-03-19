<?php declare(strict_types = 1);

namespace Room11\StackChat\Room;

use Room11\StackChat\WebSocket\Handler as WebSocketHandler;

class Room
{
    private $identifier;
    private $permanent;
    private $websocketHandler;

    public function __construct(
        Identifier $identifier,
        WebSocketHandler $websocketHandler,
        bool $permanent
    ) {
        $this->identifier = $identifier;
        $this->websocketHandler = $websocketHandler;
        $this->permanent = $permanent;
    }

    public function getIdentifier(): Identifier
    {
        return $this->identifier;
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
            'isPermanent' => $this->permanent,
            'websocketEndpoint' => $this->websocketHandler->getEndpoint()->getInfo(),
        ];
    }
}
