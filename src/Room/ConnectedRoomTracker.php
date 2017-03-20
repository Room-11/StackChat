<?php declare(strict_types = 1);

namespace Room11\StackChat\Room;

interface ConnectedRoomTracker extends \Iterator, \Countable
{
    /**
     * Determine if the identified room is connected
     *
     * @param Room|string $identifier
     * @return bool
     */
    function contains(Room $identifier): bool;
}
