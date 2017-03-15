<?php declare(strict_types = 1);

namespace Room11\StackExchangeChatClient\Client;

use Room11\StackExchangeChatClient\Room\Room as ChatRoom;

interface RoomContainer
{
    function getRoom(): ChatRoom;
}
