<?php declare(strict_types = 1);

namespace Room11\StackExchangeChatClient\WebSocket;

use Amp\Promise;
use Room11\StackExchangeChatClient\Entities\ChatMessage;
use Room11\StackExchangeChatClient\Event\Event;
use Room11\StackExchangeChatClient\Room\Identifier;

interface EventDispatcher
{
    function processWebSocketEvent(Event $event): Promise;

    function processMessageEvent(ChatMessage $message): Promise;

    function processConnect(Identifier $identifier): Promise;

    function processDisconnect(Identifier $identifier): Promise;
}
