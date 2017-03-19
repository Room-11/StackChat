<?php declare(strict_types = 1);

namespace Room11\StackChat\Auth;

use Room11\StackChat\Room\Identifier;

class ActiveSessionTracker implements SessionTracker
{
    /**
     * @var Session[]
     */
    private $sessions = [];

    public function setSessionForRoom(Identifier $room, Session $session): void
    {
        $this->sessions[$room->getIdentString()] = $session;
    }

    public function getSessionForRoom(Identifier $room): Session
    {
        //todo: throw on non-existent key?
        return $this->sessions[$room->getIdentString()];
    }
}
