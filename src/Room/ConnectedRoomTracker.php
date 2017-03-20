<?php declare(strict_types = 1);

namespace Room11\StackChat\Room;

interface ConnectedRoomTracker extends \Iterator, \Countable
{
    /**
     * Get the connected room instance
     *
     * @param Room|Identifier|string $identifier
     * @return Identifier
     * @throws InvalidRoomIdentifierException
     */
    function get($identifier): Identifier;

    /**
     * Determine if the identified room is connected
     *
     * @param Room|Identifier|string $identifier
     * @return bool
     */
    function contains($identifier): bool;
}
