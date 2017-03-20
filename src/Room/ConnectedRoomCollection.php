<?php  declare(strict_types=1);

namespace Room11\StackChat\Room;

class ConnectedRoomCollection implements ConnectedRoomTracker
{
    /**
     * @var Room[][]
     */
    private $rooms = [];

    private $currentCount = 0;

    /**
     * Collection constructor.
     * @param Room[] $rooms
     */
    public function __construct(array $rooms = [])
    {
        array_map([$this, 'add'], $rooms);
    }

    public function add(Room $room)
    {
        $this->rooms[$room->getHost()][$room->getId()] = $room;
        $this->currentCount++;
    }

    /**
     * @param Room $room
     * @throws InvalidRoomIdentifierException
     */
    public function remove(Room $room)
    {
        if (!isset($this->rooms[$room->getHost()][$room->getId()])) {
            throw new InvalidRoomIdentifierException("Unknown room identifier: {$room->getHost()}#{$room->getId()}");
        }

        unset($this->rooms[$room->getHost()][$room->getId()]);
        $this->currentCount--;
    }

    /**
     * @param Room $room
     * @return bool
     */
    public function contains(Room $room): bool
    {
        return isset($this->rooms[$room->getHost()][$room->getId()]);
    }

    /* Below this point are all array-object implementations that are just wrappers over the methods above. They
       should not modify the values in $this->rooms directly!   If you foreach over this object and call methods
       which modify the content of the collection during the loop, the results will be unpredictable because the
       Iterator implementation uses the internal array pointers. Stop writing shitty code anyway. */

    /**
     * @return Room|false
     */
    public function current()
    {
        if (null === $key = key($this->rooms)) {
            return false;
        }

        return current($this->rooms[$key]);
    }

    public function next()
    {
        if (null === $key = key($this->rooms)) {
            return;
        }

        if (next($this->rooms[$key]) !== false) {
            return;
        }

        next($this->rooms);
        if (null === $key = key($this->rooms)) {
            return;
        }

        reset($this->rooms[$key]);
    }

    /**
     * @return string|null
     */
    public function key()
    {
        if (false === $room = $this->current()) {
            return null;
        }

        return $room->getIdentString();
    }

    public function valid()
    {
        return $this->current() !== false;
    }

    public function rewind()
    {
        reset($this->rooms);

        if (null !== $key = key($this->rooms)) {
            reset($this->rooms[$key]);
        }
    }

    public function count()
    {
        return $this->currentCount;
    }
}
