<?php declare(strict_types = 1);

namespace Room11\StackExchangeChatClient\Client;

interface IdentifiableMessage extends ChatRoomContainer
{
    function getId(): int;
}
