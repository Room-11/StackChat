<?php declare(strict_types = 1);

namespace Room11\StackChat\Client;

use Room11\StackChat\Room\Room as ChatRoom;

interface RoomContainer
{
    function getRoom(): ChatRoom;
}
