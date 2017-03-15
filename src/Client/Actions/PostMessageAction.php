<?php declare(strict_types = 1);

namespace Room11\StackChat\Client\Actions;

use Amp\Artax\Request as HttpRequest;
use Psr\Log\LoggerInterface as Logger;
use Room11\StackChat\Client\MessagePostFailureException;
use Room11\StackChat\Client\PostedMessageTracker;
use Room11\StackChat\Entities\PostedMessage;
use Room11\StackChat\Message;
use Room11\StackChat\Room\Room as ChatRoom;

class PostMessageAction extends Action
{
    private $tracker;
    private $text;
    private $parentMessage;

    public function __construct(
        Logger $logger,
        HttpRequest $request,
        ChatRoom $room,
        PostedMessageTracker $tracker,
        string $text,
        ?Message $parentMessage
    ) {
        parent::__construct($logger, $request, $room);

        $this->tracker = $tracker;
        $this->text = $text;
        $this->parentMessage = $parentMessage;
    }

    public function getExceptionClassName(): string
    {
        return MessagePostFailureException::class;
    }

    public function isValid(): bool
    {
        $lastMessage = $this->tracker->peekMessage($this->room);

        return $lastMessage === null || $lastMessage->getText() !== $this->text;
    }

    public function processResponse($response, int $attempt): int
    {
        if (isset($response["id"], $response["time"])) {
            $postedMessage = new PostedMessage($this->room, $response["id"], $response["time"], $this->text, $this->parentMessage);

            $this->tracker->pushMessage($postedMessage);
            $this->succeed($postedMessage);

            return self::SUCCESS;
        }

        if (!array_key_exists('id', $response)) {
            $this->logger->error('A JSON response that I don\'t understand was received', ['response' => $response]);
            $this->fail(new MessagePostFailureException("Invalid response from server"));

            return self::FAILURE;
        }

        // sometimes we can get {"id":null,"time":null}
        // I think this happens when we repeat ourselves too quickly
        // todo: remove this if we don't get any more for a week or two (repeat message guard should prevent it)
        $delay = $attempt * 1000;
        $this->logger->error("WARN: Got a null message post response, waiting for {$delay}ms before trying again");

        return $delay;
    }
}
