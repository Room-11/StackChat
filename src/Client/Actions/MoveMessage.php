<?php declare(strict_types = 1);

namespace Room11\StackChat\Client\Actions;

class MoveMessage extends Action
{
    public function processResponse($response, int $attempt): int
    {
        if (is_int($response)) {
            $this->succeed();
            return self::SUCCESS;
        }

        $errStr = 'A JSON response with an unexpected structure was received';
        $this->logger->error($errStr, ['response' => $response]);
        $this->fail(new MessageMoveFailureException($errStr));

        return self::FAILURE;
    }
}
