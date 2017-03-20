<?php declare(strict_types = 1);

namespace Room11\StackChat\WebSocket;

use Amp\Websocket\Endpoint;
use Room11\StackChat\KeyNotFoundException;
use Room11\StackChat\Room\Identifier;

class EndpointCollection
{
    private $endpoints = [];

    public function set(Identifier $identifier, Endpoint $endpoint): void
    {
        $this->endpoints[$identifier->getIdentString()] = $endpoint;
    }

    public function get(Identifier $identifier): Endpoint
    {
        if (!isset($this->endpoints[$identifier->getIdentString()])) {
            throw new KeyNotFoundException("Key {$identifier->getIdentString()} not found in endpoint collection");
        }

        return $this->endpoints[$identifier->getIdentString()];
    }

    public function contains(Identifier $identifier): bool
    {
        return isset($this->endpoints[$identifier->getIdentString()]);
    }
}
