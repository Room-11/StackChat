<?php declare(strict_types=1);

namespace Room11\StackExchangeChatClient\Event;

class DeleteMessage extends MessageEvent
{
    const TYPE_ID = EventType::MESSAGE_DELETED;
}
