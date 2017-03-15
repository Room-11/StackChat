<?php declare(strict_types = 1);

namespace Room11\StackChat\Auth;

use Room11\StackChat\Entities\ChatUser;

class SessionFactory
{
    public function build(ChatUser $user, string $fkey, string $mainSiteUrl, string $webSocketUrl)
    {
        return new Session($user, $fkey, $mainSiteUrl, $webSocketUrl);
    }
}
