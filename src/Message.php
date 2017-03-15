<?php declare(strict_types = 1);

namespace Room11\StackExchangeChatClient;

use Room11\StackExchangeChatClient\Client\RoomContainer;

interface Message extends RoomContainer
{
    function getId(): int;
}
