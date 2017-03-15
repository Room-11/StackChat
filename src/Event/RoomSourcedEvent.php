<?php declare(strict_types=1);

namespace Room11\StackChat\Event;

use Room11\StackChat\Client\RoomContainer;

interface RoomSourcedEvent extends Event, RoomContainer {}
