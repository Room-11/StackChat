<?php declare(strict_types = 1);

namespace Room11\StackChat\Client\Actions;

use Room11\StackChat\Client\MessageEditFailureException;

class PinOrUnpinMessage extends Action
{
    public function processResponse($response, int $attempt): int
    {
        if ($response === 'ok') {
            $this->succeed();
            return self::SUCCESS;
        }

        if ($response === 'Only a room-owner can pin messages') {
            $errStr = 'Authenticated user is not a room owner.';
            $this->logger->error($errStr, ['response' => $response]);
            $this->fail(new MessageEditFailureException($errStr));
        }

        $errStr = 'A JSON response with an unexpected structure was received';
        $this->logger->error($errStr, ['response' => $response]);
        $this->fail(new MessageEditFailureException($errStr));

        return self::FAILURE;
    }
}
