<?php declare(strict_types = 1);

namespace Room11\StackChat\WebSocket;

use Amp\Promise;
use Room11\StackChat\Entities\ChatMessage;
use Room11\StackChat\Event\Event;
use Room11\StackChat\Room\Identifier;

interface EventDispatcher
{
    function processWebSocketEvent(Event $event): Promise;

    function processMessageEvent(ChatMessage $message): Promise;

    function processConnect(Identifier $identifier): Promise;

    function processDisconnect(Identifier $identifier): Promise;
}
