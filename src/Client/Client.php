<?php declare(strict_types = 1);

namespace Room11\StackChat\Client;

use Amp\Promise;
use Room11\StackChat\Message;
use Room11\StackChat\Room\Identifier;
use Room11\StackChat\Room\Room;

interface Client
{
    /**
     * @param Room|Identifier $room
     * @param int $messageId
     * @return Promise<Room>
     */
    function getRoomIdentifierFromMessageID($room, int $messageId);

    /**
     * @param Room|Identifier $room
     * @param int[] ...$ids
     * @return Promise
     */
    function getChatUsers($room, int ...$ids): Promise;

    /**
     * @param Room|Identifier $room
     * @param int[] ...$ids
     * @return Promise
     */
    function getMainSiteUsers($room, int ...$ids): Promise;

    /**
     * @param Room|Identifier $room
     * @return Promise
     */
    function getPingableUsers($room): Promise;

    /**
     * @param Room|Identifier $room
     * @param string $name
     * @return Promise
     */
    function getPingableName($room, string $name): Promise;

    /**
     * @param Room|Identifier $room
     * @param string[] $names
     * @return Promise<int[]>
     */
    function getPingableUserIDs($room, string ...$names): Promise;

    /**
     * @param Room|Identifier $room
     * @return Promise
     */
    function getPinnedMessages($room): Promise;

    /**
     * @param Room|Identifier $room
     * @param int $id
     * @return Promise
     */
    function getMessageHTML($room, int $id): Promise;

    /**
     * @param Room|Identifier $room
     * @param int $id
     * @return Promise
     */
    function getMessageText($room, int $id): Promise;

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
}
