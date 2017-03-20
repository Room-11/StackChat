<?php declare(strict_types = 1);

namespace Room11\StackChat\Client;

use Amp\Promise;
use Room11\StackChat\Message;
use Room11\StackChat\Room\Room;

interface Client
{
    /**
     * @param Room $room
     * @param int $messageId
     * @return Promise<Room>
     * @todo this method signature is obviously wrong...
     */
    function getRoomIdentifierFromMessageID(Room $room, int $messageId);

    /**
     * @param Room $room
     * @param int[] ...$ids
     * @return Promise
     */
    function getChatUsers(Room $room, int ...$ids): Promise;

    /**
     * @param Room $room
     * @param int[] ...$ids
     * @return Promise
     */
    function getMainSiteUsers(Room $room, int ...$ids): Promise;

    /**
     * @param Room $room
     * @return Promise
     */
    function getPingableUsers(Room $room): Promise;

    /**
     * @param Room $room
     * @param string $name
     * @return Promise
     */
    function getPingableName(Room $room, string $name): Promise;

    /**
     * @param Room $room
     * @param string[] $names
     * @return Promise<int[]>
     */
    function getPingableUserIDs(Room $room, string ...$names): Promise;

    /**
     * @param Room $room
     * @return Promise
     */
    function getPinnedMessages(Room $room): Promise;

    /**
     * @param Room $room
     * @param int $id
     * @return Promise
     */
    function getMessageHTML(Room $room, int $id): Promise;

    /**
     * @param Room $room
     * @param int $id
     * @return Promise
     */
    function getMessageText(Room $room, int $id): Promise;

    /**
     * @param Room|RoomContainer $target
     * @param string $text
     * @param int $flags
     * @return Promise
     */
    function postMessage($target, string $text, int $flags = PostFlags::NONE): Promise;

    /**
     * @param Room $room
     * @param int $targetRoomId
     * @param int[] ...$messageIds
     * @return Promise
     */
    function moveMessages(Room $room, int $targetRoomId, int ...$messageIds): Promise;

    /**
     * @param Message $origin
     * @param string $text
     * @param int $flags
     * @return Promise
     * @internal param string $text
     */
    function postReply(Message $origin, string $text, int $flags = PostFlags::NONE): Promise;

    /**
     * @param Message $message
     * @param string $text
     * @param int $flags
     * @return Promise
     */
    function editMessage(Message $message, string $text, int $flags = PostFlags::NONE): Promise;

    /**
     * @param Message|int $messageOrId
     * @param Room|null $room
     * @return Promise
     */
    function pinOrUnpinMessage($messageOrId, Room $room = null): Promise;

    /**
     * @param Message|int $messageOrId
     * @param Room|null $room
     * @return Promise
     */
    function unstarMessage($messageOrId, Room $room = null): Promise;

    /**
     * Leave a chat room
     *
     * @param Room $room
     * @return Promise
     */
    function leaveRoom(Room $room): Promise;
}
