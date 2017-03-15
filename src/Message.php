<?php declare(strict_types = 1);

namespace Room11\StackChat;

use Room11\StackChat\Client\RoomContainer;

interface Message extends RoomContainer
{
    function getId(): int;
}
