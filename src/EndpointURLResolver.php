<?php declare(strict_types = 1);

namespace Room11\StackChat;

use Room11\StackChat\Auth\ActiveSessionTracker;
use Room11\StackChat\Room\ConnectedRoomCollection;
use Room11\StackChat\Room\Room;

class EndpointURLResolver
{
    private static $endpointURLTemplates = [
        Endpoint::CHATROOM_UI                  => 'https://%1$s/rooms/%2$d',
        Endpoint::CHATROOM_WEBSOCKET_AUTH      => 'https://%1$s/ws-auth',
        Endpoint::CHATROOM_EVENT_HISTORY       => 'https://%1$s/chats/%2$d/events',
        Endpoint::CHATROOM_STARS_LIST          => 'https://%1$s/chats/stars/%2$d?count=0',

        Endpoint::CHATROOM_GET_MESSAGE_HTML    => 'https://%1$s/message/%3$d',
        Endpoint::CHATROOM_POST_MESSAGE        => 'https://%1$s/chats/%2$d/messages/new',
        Endpoint::CHATROOM_EDIT_MESSAGE        => 'https://%1$s/messages/%3$d',
        Endpoint::CHATROOM_MOVE_MESSAGE        => 'https://%1$s/admin/movePosts/%2$d',
        Endpoint::CHATROOM_PIN_MESSAGE         => 'https://%1$s/messages/%3$d/owner-star',
        Endpoint::CHATROOM_UNSTAR_MESSAGE      => 'https://%1$s/messages/%3$d/unstar',
        Endpoint::CHATROOM_GET_MESSAGE_TEXT    => 'https://%1$s/messages/%2$d/%3$d',
        Endpoint::CHATROOM_GET_MESSAGE_HISTORY => 'https://%1$s/messages/%3$d/history',
        Endpoint::CHATROOM_LEAVE               => 'https://%1$s/chats/leave/%2$d',

        Endpoint::CHATROOM_INFO_GENERAL        => 'https://%1$s/rooms/info/%2$d?tab=general',
        Endpoint::CHATROOM_INFO_STARS          => 'https://%1$s/rooms/info/%2$d?tab=stars',
        Endpoint::CHATROOM_INFO_CONVERSATIONS  => 'https://%1$s/rooms/info/%2$d?tab=conversations',
        Endpoint::CHATROOM_INFO_SCHEDULE       => 'https://%1$s/rooms/info/%2$d?tab=schedule',
        Endpoint::CHATROOM_INFO_FEEDS          => 'https://%1$s/rooms/info/%2$d?tab=feeds',
        Endpoint::CHATROOM_INFO_ACCESS         => 'https://%1$s/rooms/info/%2$d?tab=access',
        Endpoint::CHATROOM_INFO_PINGABLE       => 'https://%1$s/rooms/pingable/%2$d',

        Endpoint::CHAT_USER                    => 'https://%1$s/users/%3$d',
        Endpoint::CHAT_USER_INFO               => 'https://%1$s/user/info',
        Endpoint::CHAT_USER_INFO_EXTRA         => 'https://%1$s/users/thumbs/%3$d?showUsage=false',

        Endpoint::MAINSITE_USER                => '%1$s/users/%2$d?tab=profile',
        Endpoint::MAINSITE_MODERATOR_LIST      => '%1$s/users?tab=moderators',
    ];

    private $connectedRooms;
    private $sessions;

    private function getChatEndpointURL(Room $room, int $endpoint, array $extraData): string
    {
        return sprintf(
            self::$endpointURLTemplates[$endpoint],
            $room->getHost(),
            $room->getId(),
            ...$extraData
        );
    }

    private function getMainSiteEndpointURL(Room $room, int $endpoint, array $extraData): string
    {
        return sprintf(
            self::$endpointURLTemplates[$endpoint],
            rtrim($this->sessions->getSessionForRoom($room)->getMainSiteUrl(), '/'),
            ...$extraData
        );
    }

    public function __construct(ConnectedRoomCollection $connectedRooms, ActiveSessionTracker $sessions)
    {
        $this->connectedRooms = $connectedRooms;
        $this->sessions = $sessions;
    }

    /**
     * @param Room $room
     * @param int $endpoint
     * @param array $extraData
     * @return string
     */
    public function getEndpointURL(Room $room, int $endpoint, ...$extraData): string
    {
        if (!isset(self::$endpointURLTemplates[$endpoint])) {
            throw new \LogicException('Invalid endpoint ID');
        }

        if ($endpoint < Endpoint::MAINSITE_URLS_START) {
            return $this->getChatEndpointURL($room, $endpoint, $extraData);
        }

        return $this->getMainSiteEndpointURL($room, $endpoint, $extraData);
    }
}
