<?php declare(strict_types=1);

namespace Room11\StackChat\Room;

use Amp\Promise;
use Amp\Websocket\Handshake;
use Room11\StackChat\Auth\Authenticator;
use Room11\StackChat\Auth\Session;
use Room11\StackChat\WebSocket\HandlerFactory as WebSocketHandlerFactory;
use function Amp\resolve;
use function Amp\websocket;

class Connector
{
    private $authenticator;
    private $handlerFactory;

    public function __construct(
        Authenticator $authenticator,
        WebSocketHandlerFactory $handlerFactory
    ) {
        $this->authenticator = $authenticator;
        $this->handlerFactory = $handlerFactory;
    }

    public function connect(Identifier $identifier, bool $permanent): Promise
    {
        return resolve(function() use($identifier, $permanent) {
            /** @var Session $sessionInfo */
            $sessionInfo = yield $this->authenticator->getRoomSessionInfo($identifier);

            $handshake = (new Handshake($sessionInfo->getWebSocketUrl()))
                ->setHeader('Origin', 'https://' . $identifier->getHost());
            $handler = $this->handlerFactory->build($identifier);

            yield websocket($handler, $handshake);

            return new Room($identifier, $sessionInfo, $handler, $permanent);
        });
    }
}
