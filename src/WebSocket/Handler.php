<?php declare(strict_types = 1);

namespace Room11\StackChat\WebSocket;

use Amp\Websocket;
use Amp\Websocket\Endpoint as WebSocketEndpoint;
use Amp\Websocket\Message as WebSocketMessage;
use ExceptionalJSON\DecodeErrorException as JSONDecodeErrorException;
use Psr\Log\LoggerInterface as Logger;
use Room11\StackChat\Entities\ChatMessage;
use Room11\StackChat\Event\Builder as EventBuilder;
use Room11\StackChat\Event\Event;
use Room11\StackChat\Event\GlobalEvent;
use Room11\StackChat\Event\MessageEvent;
use Room11\StackChat\Room\ConnectedRoomCollection;
use Room11\StackChat\Room\Room;
use function Amp\cancel;
use function Amp\once;

class Handler implements Websocket
{
    private const HEARTBEAT_TIMEOUT_SECONDS = 40;

    private $eventBuilder;
    private $eventDispatcher;
    private $endpoints;
    private $rooms;
    private $logger;
    private $room;

    /**
     * @var WebsocketEndpoint
     */
    private $endpoint;

    private $timeoutWatcherId;

    public function __construct(
        EventBuilder $eventBuilder,
        EventDispatcher $eventDispatcher,
        EndpointCollection $endpoints,
        ConnectedRoomCollection $rooms,
        Logger $logger,
        Room $room
    ) {
        $this->eventBuilder = $eventBuilder;
        $this->eventDispatcher = $eventDispatcher;
        $this->endpoints = $endpoints;
        $this->rooms = $rooms;
        $this->logger = $logger;
        $this->room = $room;
    }

    private function clearTimeoutWatcher()
    {
        if ($this->timeoutWatcherId !== null) {
            $this->logger->debug("Cancelling timeout watcher #{$this->timeoutWatcherId}");

            cancel($this->timeoutWatcherId);
            $this->timeoutWatcherId = null;
        }
    }

    private function setTimeoutWatcher(int $secs = self::HEARTBEAT_TIMEOUT_SECONDS)
    {
        $this->timeoutWatcherId = once(function() {
            $this->logger->debug("Connection to {$this->room} timed out");

            $this->endpoint->close();
        }, $secs * 1000);

        $this->logger->debug("Created timeout watcher #{$this->timeoutWatcherId}");
    }

    public function onOpen(WebsocketEndpoint $endpoint, array $headers)
    {
        try {
            $this->logger->debug("Connection to {$this->room} established");

            $this->endpoints->set($this->room, $endpoint);
            $this->rooms->add($this->room);

            // we expect a heartbeat message from the server immediately on connect, if we don't get one then try again
            // this seems to happen a lot while testing, I'm not sure if it's an issue with the server or us (it's
            // probably us)
            $this->setTimeoutWatcher(2);
        } catch (\Throwable $e) {
            $this->logger->error(
                "Something went generally wrong while opening connection to {$this->room}: $e"
            );
        }
    }

    public function onData(WebsocketMessage $websocketMessage)
    {
        try {
            $rawWebsocketMessage = yield $websocketMessage;

            $this->logger->debug("Websocket message received on connection to {$this->room}", ['message' => $rawWebsocketMessage]);

            $this->clearTimeoutWatcher();
            $this->setTimeoutWatcher();

            try {
                $data = json_try_decode($rawWebsocketMessage, true);
            } catch (JSONDecodeErrorException $e) {
                $this->logger->error("Error decoding JSON message from server: {$e->getMessage()}");
                return;
            }

            /** @var Event[] $events */
            $events = yield from $this->eventBuilder->build($data, $this->room);
            $this->logger->debug(count($events) . " events targeting {$this->room} to process");

            foreach ($events as $event) {
                yield $this->eventDispatcher->onWebSocketEvent($event);

                if ($event instanceof MessageEvent && !$event instanceof GlobalEvent) {
                    $this->eventDispatcher->onMessageEvent(new ChatMessage($event));
                }
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                "Something went generally wrong while processing events for {$this->room}: $e"
            );
        }
    }

    public function onClose($code, $reason)
    {
        try {
            $this->clearTimeoutWatcher();

            $this->rooms->remove($this->room);

            $this->logger->debug("Connection to {$this->room} closed");
            yield $this->eventDispatcher->onDisconnect($this->room);
        } catch (\Throwable $e) {
            $this->logger->error(
                "Something went generally wrong while handling closure of connection to {$this->room}: $e"
            );
        }
    }
}
