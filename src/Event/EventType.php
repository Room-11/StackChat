<?php declare(strict_types = 1);

namespace Room11\StackChat\Event;

// https://github.com/awalGarg/sochatbot/blob/417484f5031d4d9e4adf29ca4b6423b4ebfaa472/sechatapi/eventmaps.json
final class EventType
{
    const MESSAGE_POSTED = 1;
    const MESSAGE_EDITED = 2;
    const USER_JOINED = 3;
    const USER_LEFT = 4;
    const ROOM_INFO_UPDATED = 5;
    const MESSAGE_STARRED = 6;
    const DEBUG_MESSAGE = 7;
    const USER_MENTIONED = 8;
    const MESSAGE_FLAGGED = 9;
    const MESSAGE_DELETED = 10;
    const FILE_ADDED = 11;
    const MODERATOR_FLAG_RAISED = 12;
    const USER_SETTINGS_CHANGED = 13;
    const GLOBAL_NOTIFICATION = 14;
    const ACCESS_LEVEL_CHANGED = 15;
    const USER_NOTIFICATION = 16;
    const INVITATION = 17;
    const MESSAGE_REPLY = 18;
    const MESSAGE_MOVED_OUT = 19;
    const MESSAGE_MOVED_IN = 20;
    const TIME_BREAK = 21;
    const FEED_TICKER = 22;
    const USER_SUSPENDED = 29;
    const USER_MERGED = 30;
    const USER_NAME_OR_AVATAR_CHANGED = 34;

    private function __construct() {}
}
