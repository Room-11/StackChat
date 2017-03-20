<?php declare(strict_types = 1);

namespace Room11\StackChat\WebSocket;

use Room11\StackChat\Room\Identifier;

/**
 * @todo kill this before it lays eggs
 */
interface EventDispatcherFactory
{
    function createEventDispatcher(Identifier $room): EventDispatcher;
}
