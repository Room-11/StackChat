<?php declare(strict_types=1);

namespace Room11\StackChat\Event\Traits;

use Room11\StackChat\Room\Room as ChatRoom;

trait RoomSource
{
    private $room;

    public function getRoom(): ChatRoom
    {
        return $this->room;
    }
}
