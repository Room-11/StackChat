<?php declare(strict_types = 1);

namespace Room11\StackChat\Client\Actions;

use Amp\Artax\Request;
use Psr\Log\LoggerInterface as Logger;
use Room11\StackChat\Client\PostedMessageTracker;
use Room11\StackChat\Message;
use Room11\StackChat\Room\Room as ChatRoom;

class Factory
{
    private $logger;
    private $tracker;

    public function __construct(Logger $logger, PostedMessageTracker $tracker)
    {
        $this->logger = $logger;
        $this->tracker = $tracker;
    }

    public function createPostMessageAction(Request $request, ChatRoom $room, string $text, ?Message $parentMessage): PostMessage
    {
        return new PostMessage($this->logger, $request, $room, $this->tracker, $text, $parentMessage);
    }

    public function createEditMessageAction(Request $request, ChatRoom $room): EditMessage
    {
        return new EditMessage($this->logger, $request, $room);
    }

    public function createMoveMessageAction(Request $request, ChatRoom $room): MoveMessage
    {
        return new MoveMessage($this->logger, $request, $room);
    }

    public function createPinOrUnpinMessageAction(Request $request, ChatRoom $room): PinOrUnpinMessage
    {
        return new PinOrUnpinMessage($this->logger, $request, $room);
    }

    public function createUnstarMessageAction(Request $request, ChatRoom $room): UnstarMessage
    {
        return new UnstarMessage($this->logger, $request, $room);
    }
}
