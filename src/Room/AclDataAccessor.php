<?php declare(strict_types = 1);

namespace Room11\StackChat\Room;

use Amp\Promise;

interface AclDataAccessor
{
    /**
     * @param Room|Identifier $room
     * @return Promise<string[][]>
     */
    function getRoomAccess($room): Promise;

    /**
     * @param Room|Identifier $room
     * @return Promise<string[]>
     */
    function getRoomOwners($room): Promise;

    /**
     * @param Room|Identifier $room
     * @param int $userId
     * @return Promise<bool>
     */
    function isRoomOwner($room, int $userId): Promise;

    /**
     * @param Room|Identifier $room
     * @return Promise<bool>
     */
    function isAuthenticatedUserRoomOwner(Room $room): Promise;
}
