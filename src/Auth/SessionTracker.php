<?php declare(strict_types = 1);

namespace Room11\StackChat\Auth;

use Room11\StackChat\Room\Identifier;

interface SessionTracker
{
    function getSessionForRoom(Identifier $room): Session;
}
