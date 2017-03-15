<?php declare(strict_types=1);

namespace Room11\StackExchangeChatClient\Room;

use Room11\StackExchangeChatClient\Auth\Session;
use Room11\StackExchangeChatClient\WebSocket\Handler;
use Room11\StackExchangeChatClient\Storage\KeyValueFactory as KeyValueStorageFactory;

class RoomFactory
{
    private $keyValueStorageFactory;

    public function __construct(KeyValueStorageFactory $keyValueStorageFactory)
    {
        $this->keyValueStorageFactory = $keyValueStorageFactory;
    }

    public function build(Identifier $identifier, Session $session, Handler $handler, PresenceManager $presenceManager, bool $permanent)
    {
        $keyValueStore = $this->keyValueStorageFactory->build($identifier->getIdentString());

        return new Room($identifier, $session, $handler, $presenceManager, $keyValueStore, $permanent);
    }
}
