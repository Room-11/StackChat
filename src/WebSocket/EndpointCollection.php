<?php declare(strict_types = 1);

namespace Room11\StackChat\WebSocket;

use Amp\Websocket\Endpoint;
use Room11\StackChat\KeyNotFoundException;
use Room11\StackChat\Room\Room;

class EndpointCollection
{
    private $endpoints = [];

    public function set(Room $room, Endpoint $endpoint): void
    {
        $this->endpoints[$room->getIdentString()] = $endpoint;
    }

    public function get(Room $room): Endpoint
    {
        if (!isset($this->endpoints[$room->getIdentString()])) {
            throw new KeyNotFoundException("Key {$room->getIdentString()} not found in endpoint collection");
        }

        return $this->endpoints[$room->getIdentString()];
    }

    public function contains(Room $room): bool
    {
        return isset($this->endpoints[$room->getIdentString()]);
    }
}
