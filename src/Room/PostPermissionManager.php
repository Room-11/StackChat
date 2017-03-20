<?php declare(strict_types = 1);

namespace Room11\StackChat\Room;

use Amp\Promise;

interface PostPermissionManager
{
    public function isPostAllowed(Room $identifier): Promise;
}
