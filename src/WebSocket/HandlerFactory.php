<?php  declare(strict_types=1);

namespace Room11\StackChat\WebSocket;

use Psr\Log\LoggerInterface as Logger;
use Room11\StackChat\Event\Builder as EventBuilder;
use Room11\StackChat\Room\Identifier as ChatRoomIdentifier;

class HandlerFactory
{
    private $eventBuilder;
    private $eventDispatcher;
    private $logger;

    public function __construct(
        EventBuilder $eventBuilder,
        EventDispatcher $eventDispatcher,
        Logger $logger
    ) {
        $this->eventBuilder = $eventBuilder;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
    }

    public function build(ChatRoomIdentifier $identifier)
    {
        return new Handler($this->eventBuilder, $this->eventDispatcher, $this->logger, $identifier);
    }
}
