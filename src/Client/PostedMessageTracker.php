<?php declare(strict_types = 1);

namespace Room11\StackChat\Client;

use Ds\Deque;
use Room11\StackChat\Entities\PostedMessage;
use Room11\StackChat\Room\Room;

class PostedMessageTracker
{
    private const BUFFER_SIZE = 20;

    /**
     * @var Deque[]
     */
    private $messages = [];

    public function pushMessage(PostedMessage $message)
    {
        $ident = $message->getRoom()->getIdentString();

        if (!isset($this->messages[$ident])) {
            $this->messages[$ident] = new Deque;
        }

        $this->messages[$ident]->push($message);

        if ($this->messages[$ident]->count() > self::BUFFER_SIZE) {
            $this->messages[$ident]->shift();
        }
    }

    /**
     * @param Room $room
     * @return PostedMessage
     */
    public function popMessage(Room $room)
    {
        $ident = $room->getIdentString();

        if (!isset($this->messages[$ident]) || !$this->messages[$ident] instanceof Deque) {
            return null;
        }

        $message = $this->messages[$ident]->pop();

        if ($this->messages[$ident]->isEmpty()) {
            unset($this->messages[$ident]);
        }

        return $message;
    }

    /**
     * @param Room $room
     * @return PostedMessage
     */
    public function peekMessage(Room $room)
    {
        $ident = $room->getIdentString();

        return isset($this->messages[$ident])
            ? $this->messages[$ident]->last()
            : null;
    }

    /**
     * @param Room $room
     * @return PostedMessage[]
     */
    public function getAll(Room $room): array
    {
        $ident = $room->getIdentString();

        return isset($this->messages[$ident])
            ? $this->messages[$ident]->toArray()
            : [];
    }

    /**
     * @param Room $room
     * @return int
     */
    public function getCount(Room $room): int
    {
        $ident = $room->getIdentString();

        return isset($this->messages[$ident])
            ? $this->messages[$ident]->count()
            : 0;
    }
}
