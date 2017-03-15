<?php declare(strict_types=1);

namespace Room11\StackExchangeChatClient\Room;

use Amp\Promise;
use Room11\StackExchangeChatClient\Auth\Authenticator;
use Room11\StackExchangeChatClient\Auth\Session;
use Room11\StackExchangeChatClient\WebSocket\HandlerFactory as WebSocketHandlerFactory;
use Room11\StackExchangeChatClient\WebSocket\HandshakeFactory as WebSocketHandshakeFactory;
use function Amp\resolve;
use function Amp\websocket;

class Connector
{
    private $authenticator;
    private $handshakeFactory;
    private $handlerFactory;

    public function __construct(
        Authenticator $authenticator,
        WebSocketHandshakeFactory $handshakeFactory,
        WebSocketHandlerFactory $handlerFactory
    ) {
        $this->authenticator = $authenticator;
        $this->handshakeFactory = $handshakeFactory;
        $this->handlerFactory = $handlerFactory;
    }

    public function connect(Identifier $identifier, bool $permanent): Promise
    {
        return resolve(function() use($identifier, $permanent) {
            /** @var Session $sessionInfo */
            $sessionInfo = yield $this->authenticator->getRoomSessionInfo($identifier);

            $handshake = $this->handshakeFactory->build($sessionInfo->getWebSocketUrl())
                ->setHeader('Origin', 'https://' . $identifier->getHost());
            $handler = $this->handlerFactory->build($identifier);

            yield websocket($handler, $handshake);

            return new Room($identifier, $sessionInfo, $handler, $permanent);
        });
    }
}
