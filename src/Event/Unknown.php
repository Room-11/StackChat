<?php declare(strict_types=1);

namespace Room11\StackExchangeChatClient\Event;

use Room11\StackExchangeChatClient\Room\Room as ChatRoom;

class Unknown extends BaseEvent
{
    public function __construct(array $data, ChatRoom $room)
    {
        parent::__construct($data, $room->getIdentifier()->getHost());
    }
}
