<?php  declare(strict_types=1);

namespace Room11\StackChat\WebSocket;

use Psr\Log\LoggerInterface as Logger;
use Room11\StackChat\Event\Builder as EventBuilder;
use Room11\StackChat\Room\ConnectedRoomCollection;
use Room11\StackChat\Room\Identifier as ChatRoomIdentifier;

class HandlerFactory
{
    private $eventBuilder;
    private $endpointCollection;
    private $rooms;
    private $logger;

    public function __construct(
        EventBuilder $eventBuilder,
        EndpointCollection $endpointCollection,
        ConnectedRoomCollection $rooms,
        Logger $logger
    ) {
        $this->eventBuilder = $eventBuilder;
        $this->endpointCollection = $endpointCollection;
        $this->rooms = $rooms;
        $this->logger = $logger;
    }

    public function build(ChatRoomIdentifier $identifier, EventDispatcher $eventDispatcher)
    {
        return new Handler(
            $this->eventBuilder, $eventDispatcher, $this->endpointCollection,
            $this->rooms, $this->logger, $identifier
        );
    }
}
