<?php declare(strict_types = 1);

namespace Room11\StackChat\Room;

class UserAccessType
{
    const READ_ONLY = 'read-only';
    const READ_WRITE = 'read-write';
    const OWNER = 'owner';
    const SITE_MODERATOR = 'site-moderator';
}
