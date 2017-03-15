<?php declare(strict_types = 1);

namespace Room11\StackExchangeChatClient\Room;

use Amp\Promise;

interface PostPermissionManager
{
    public function isPostAllowed(Identifier $identifier): Promise;
}
