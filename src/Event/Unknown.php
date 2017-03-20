<?php declare(strict_types=1);

namespace Room11\StackChat\Event;

use Room11\StackChat\Room\Room as ChatRoom;

class Unknown extends BaseEvent
{
    public function __construct(array $data, ChatRoom $room)
    {
        parent::__construct($data, $room->getHost());
    }
}
