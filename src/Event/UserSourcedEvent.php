<?php declare(strict_types=1);

namespace Room11\StackChat\Event;

interface UserSourcedEvent extends Event
{
    function getUserId(): int;
    function getUserName(): string;
}
