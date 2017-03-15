<?php  declare(strict_types=1);
namespace Room11\StackExchangeChatClient\WebSocket;

use Room11\StackExchangeChatClient\Event\Builder as EventBuilder;
use Room11\StackExchangeChatClient\Room\Identifier as ChatRoomIdentifier;
use Room11\StackExchangeChatClient\Room\PresenceManager;
use Room11\StackExchangeChatClient\Log\Logger;

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

    public function build(ChatRoomIdentifier $identifier, PresenceManager $presenceManager)
    {
        return new Handler(
            $this->eventBuilder, $this->eventDispatcher, $this->logger,
            $presenceManager, $identifier
        );
    }
}
