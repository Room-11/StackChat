<?php declare(strict_types = 1);

namespace Room11\StackChat\Client\Actions;

use Amp\Artax\Request as HttpRequest;
use Amp\Deferred;
use Amp\Promisor;
use Psr\Log\LoggerInterface as Logger;
use Room11\StackChat\Client\ActionExecutionFailureException;
use Room11\StackChat\Client\RoomContainer;
use Room11\StackChat\Room\Room as ChatRoom;

abstract class Action implements Promisor, RoomContainer
{
    const SUCCESS = -1;
    const FAILURE = 0;

    private $deferred;

    protected $logger;
    protected $request;
    protected $room;

    public function __construct(Logger $logger, HttpRequest $request, ChatRoom $room)
    {
        $this->logger = $logger;
        $this->request = $request;
        $this->room = $room;

        $this->deferred = new Deferred();
    }

    final public function getRequest(): HttpRequest
    {
        return $this->request;
    }

    final public function getRoom(): ChatRoom
    {
        return $this->room;
    }

    final public function promise()
    {
        return $this->deferred->promise();
    }

    final public function update($progress)
    {
        $this->deferred->update($progress);
    }

    final public function succeed($result = null)
    {
        $this->deferred->succeed($result);
    }

    final public function fail($error)
    {
        $this->deferred->fail($error);
    }

    public function isValid(): bool
    {
        return true;
    }

    public function getExceptionClassName(): string
    {
        return ActionExecutionFailureException::class;
    }

    public function getMaxAttempts(): int
    {
        return 5;
    }

    abstract public function processResponse($response, int $attempt): int;
}
