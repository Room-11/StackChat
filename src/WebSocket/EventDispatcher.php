<?php declare(strict_types = 1);

namespace Room11\StackExchangeChatClient\WebSocket;

use Amp\Promise;
use Ds\Deque;
use Room11\StackExchangeChatClient\Event\Event;
use Room11\StackExchangeChatClient\Event\GlobalEvent;
use Room11\StackExchangeChatClient\Event\MessageEvent;
use Room11\StackExchangeChatClient\Event\RoomSourcedEvent;
use Room11\StackExchangeChatClient\Message\Command;
use Room11\StackExchangeChatClient\Message\Factory as MessageFactory;
use Room11\StackExchangeChatClient\Room\Identifier;
use Psr\Log\LoggerInterface as Logger;
use Room11\StackExchangeChatClient\System\BuiltInActionManager;
use Room11\StackExchangeChatClient\System\PluginManager;
use function Amp\resolve;

class EventDispatcher
{
    private const BUFFER_SIZE = 20;

    private $pluginManager;
    private $builtInActionManager;
    private $messageFactory;
    private $logger;
    private $devMode;

    private $recentGlobalEventBuffer;

    public function __construct(
        PluginManager $pluginManager,
        BuiltInActionManager $builtInActionManager,
        MessageFactory $messageFactory,
        Logger $logger,
        bool $devMode
    ) {
        $this->pluginManager = $pluginManager;
        $this->builtInActionManager = $builtInActionManager;
        $this->messageFactory = $messageFactory;
        $this->logger = $logger;
        $this->devMode = $devMode;

        $this->recentGlobalEventBuffer = new Deque;
    }

    private function processGlobalEvent(GlobalEvent $event)
    {
        $eventId = $event->getId();

        if ($this->recentGlobalEventBuffer->contains($eventId)) {
            return;
        }

        $this->recentGlobalEventBuffer[] = $eventId;
        if ($this->recentGlobalEventBuffer->count() > self::BUFFER_SIZE) {
            $this->recentGlobalEventBuffer->shift();
        }

        $this->logger->info("Processing global event #{$eventId}", ['event' => $event]);

        $this->logger->debug("Processing global event #{$eventId} for built in event handlers");
        yield $this->builtInActionManager->handleEvent($event);
        $this->logger->debug("Event #{$eventId} processed for built in event handlers");

        $this->logger->debug("Processing global event #{$eventId} for plugins");
        yield $this->pluginManager->handleGlobalEvent($event);
        $this->logger->debug("Event #{$eventId} processed for plugins");
    }

    private function processRoomEvent(Event $event)
    {
        $eventId = $event->getId();
        $this->logger->info("Processing room event #{$eventId}", ['event' => $event]);

        try {
            $this->logger->debug("Processing room event #{$eventId} for built in event handlers");
            yield $this->builtInActionManager->handleEvent($event);
            $this->logger->debug("Event #{$eventId} processed for built in event handlers");

            $chatMessage = null;

            if ($event instanceof MessageEvent && ($this->devMode || $event->getUserId() !== $event->getRoom()->getSession()->getUser()->getId())) {
                $chatMessage = yield $this->messageFactory->build($event);

                if ($chatMessage instanceof Command) {
                    $this->logger->debug("Processing room event #{$eventId} for built in commands");
                    yield $this->builtInActionManager->handleCommand($chatMessage);
                    $this->logger->debug("Event #{$eventId} processed for built in commands");
                } else {
                    $this->logger->debug("Event #{$eventId} is not a command, it's a " . get_class($chatMessage));
                }
            }

            if (!$event instanceof RoomSourcedEvent) { // probably an Unknown event
                return;
            }

            $this->logger->debug("Processing room event #{$eventId} for plugins");
            yield $this->pluginManager->handleRoomEvent($event, $chatMessage);
            $this->logger->debug("Event #{$eventId} processed for plugins");
        } catch (\Throwable $e) {
            $this->logger->debug("Something went wrong while processing event #{$eventId}: $e");
        }
    }

    public function processWebSocketEvent(Event $event): Promise
    {
        return resolve(
            $event instanceof GlobalEvent
                ? $this->processGlobalEvent($event)
                : $this->processRoomEvent($event)
        );
    }

    public function processConnect(Identifier $identifier): Promise
    {
        return $this->pluginManager->enableAllPluginsForRoom($identifier);
    }

    public function processDisconnect(Identifier $identifier): Promise
    {
        return $this->pluginManager->disableAllPluginsForRoom($identifier);
    }
}
