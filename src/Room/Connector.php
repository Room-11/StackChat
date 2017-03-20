<?php declare(strict_types=1);

namespace Room11\StackChat\Room;

use Amp\Promise;
use Amp\Websocket\Handshake;
use Room11\StackChat\Auth\ActiveSessionTracker;
use Room11\StackChat\Auth\Authenticator;
use Room11\StackChat\Auth\Session;
use Room11\StackChat\WebSocket\EventDispatcher;
use Room11\StackChat\WebSocket\HandlerFactory as WebSocketHandlerFactory;
use function Amp\resolve;
use function Amp\websocket;

class Connector
{
    private $authenticator;
    private $sessions;
    private $handlerFactory;

    public function __construct(
        Authenticator $authenticator,
        ActiveSessionTracker $sessions,
        WebSocketHandlerFactory $handlerFactory
    ) {
        $this->authenticator = $authenticator;
        $this->sessions = $sessions;
        $this->handlerFactory = $handlerFactory;
    }

    public function connect(Room $room, EventDispatcher $eventDispatcher, bool $permanent): Promise
    {
        return resolve(function() use($room, $eventDispatcher, $permanent) {
            /** @var Session $session */
            $session = yield $this->authenticator->getRoomSessionInfo($room);
            $this->sessions->setSessionForRoom($room, $session);

            $handshake = (new Handshake($session->getWebSocketUrl()))
                ->setHeader('Origin', 'https://' . $room->getHost());

            $handler = $this->handlerFactory->build($room, $eventDispatcher);

            yield websocket($handler, $handshake);
        });
    }
}
