<?php declare(strict_types=1);

namespace Room11\StackChat\Event;

use Room11\StackChat\Event\Traits\RoomSource;
use Room11\StackChat\Event\Traits\UserSource;
use Room11\StackChat\Room\Room as ChatRoom;

class RoomEdit extends BaseEvent implements RoomSourcedEvent, UserSourcedEvent
{
    use RoomSource, UserSource;

    const TYPE_ID = EventType::ROOM_INFO_UPDATED;

    private $content;

    public function __construct(array $data, ChatRoom $room)
    {
        parent::__construct($data, $room->getHost());

        $this->room      = $room;

        $this->userId    = $data['user_id'];
        $this->userName  = $data['user_name'];

        $this->content   = $data['content'];
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
