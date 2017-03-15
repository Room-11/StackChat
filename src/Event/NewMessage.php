<?php declare(strict_types=1);

namespace Room11\StackChat\Event;

class NewMessage extends MessageEvent
{
    const TYPE_ID = EventType::MESSAGE_POSTED;
}
