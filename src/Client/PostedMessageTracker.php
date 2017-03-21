<?php declare(strict_types = 1);

namespace Room11\StackChat\Client;

use Ds\Deque;
use Room11\StackChat\Entities\PostedMessage;
use Room11\StackChat\Room\Room;

class PostedMessageTracker
{
    private const DEFAULT_BUFFER_SIZE = 20;

    private $bufferSize;

    /**
     * @var Deque[]
     */
    private $messages = [];

    public function __construct(int $bufferSize = self::DEFAULT_BUFFER_SIZE)
    {
        $this->bufferSize = $bufferSize;
    }

    public function pushMessage(PostedMessage $message): void
    {
        $ident = $message->getRoom()->getIdentString();

        if (!isset($this->messages[$ident])) {
            $this->messages[$ident] = new Deque;
        }

        $this->messages[$ident]->push($message);

        if ($this->messages[$ident]->count() > $this->bufferSize) {
            $this->messages[$ident]->shift();
        }
    }

    public function popMessage(Room $room): ?PostedMessage
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

    public function peekMessage(Room $room): ?PostedMessage
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

    public function getCount(Room $room): int
    {
        $ident = $room->getIdentString();

        return isset($this->messages[$ident])
            ? $this->messages[$ident]->count()
            : 0;
    }

    public function getBufferSize(): int
    {
        return $this->bufferSize;
    }

    public function setBufferSize(int $bufferSize)
    {
        $this->bufferSize = $bufferSize;

        foreach ($this->messages as $deque) {
            $deque->allocate($bufferSize);
        }
    }
}
