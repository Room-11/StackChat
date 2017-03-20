<?php declare(strict_types = 1);

namespace Room11\StackChat\Auth;

use Room11\StackChat\Room\Room;

interface SessionTracker
{
    function getSessionForRoom(Room $room): Session;
}
