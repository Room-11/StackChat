<?php declare(strict_types = 1);

namespace Room11\StackExchangeChatClient\Entities;

use Room11\StackExchangeChatClient\Message;
use Room11\StackExchangeChatClient\Room\Room as ChatRoom;

class PostedMessage implements Message
{
    private $room;
    private $id;
    private $timestamp;
    private $text;
    private $parentMessage;

    public function __construct(ChatRoom $room, int $id, int $timestamp, string $text, ?Message $parentMessage)
    {
        $this->room = $room;
        $this->id = $id;
        $this->timestamp = new \DateTimeImmutable("@{$timestamp}");
        $this->text = $text;
        $this->parentMessage = $parentMessage;
    }

    public function getRoom(): ChatRoom
    {
        return $this->room;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getParentMessage(): ?Message
    {
        return $this->parentMessage;
    }

    public function getTimestamp(): \DateTimeImmutable
    {
        return $this->timestamp;
    }
}
