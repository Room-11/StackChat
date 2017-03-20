<?php declare(strict_types = 1);

namespace Room11\StackChat\Room;

use Amp\Promise;

interface AclDataAccessor
{
    /**
     * @param Room $room
     * @return Promise<string[][]>
     */
    function getRoomAccess(Room $room): Promise;

    /**
     * @param Room $room
     * @return Promise<string[]>
     */
    function getRoomOwners(Room $room): Promise;

    /**
     * @param Room $room
     * @param int $userId
     * @return Promise<bool>
     */
    function isRoomOwner(Room $room, int $userId): Promise;

    /**
     * @param Room $room
     * @return Promise<bool>
     */
    function isAuthenticatedUserRoomOwner(Room $room): Promise;
}
