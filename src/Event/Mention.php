<?php declare(strict_types=1);

namespace Room11\StackChat\Event;

class Mention extends MessageEvent implements GlobalEvent
{
    const TYPE_ID = EventType::USER_MENTIONED;
}
