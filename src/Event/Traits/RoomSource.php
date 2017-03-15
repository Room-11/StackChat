<?php declare(strict_types=1);

namespace Room11\StackExchangeChatClient\Event\Traits;

use Room11\StackExchangeChatClient\Room\Room as ChatRoom;

trait RoomSource
{
    private $room;

    public function getRoom(): ChatRoom
    {
        return $this->room;
    }
}
