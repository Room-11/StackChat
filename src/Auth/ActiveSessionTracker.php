<?php declare(strict_types = 1);

namespace Room11\StackChat\Auth;

use Room11\StackChat\KeyNotFoundException;
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
        if (!isset($this->sessions[$room->getIdentString()])) {
            throw new KeyNotFoundException("Key {$room->getIdentString()} not found in session tracker");
        }

        return $this->sessions[$room->getIdentString()];
    }

    public function hasSessionForRoom(Identifier $room): bool
    {
        return isset($this->sessions[$room->getIdentString()]);
    }
}
