<?php declare(strict_types=1);

namespace Room11\StackExchangeChatClient\Event;

use Room11\StackExchangeChatClient\Client\RoomContainer;

interface RoomSourcedEvent extends Event, RoomContainer {}
