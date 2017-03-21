<?php declare(strict_types = 1);

namespace Room11\StackChat\Client\Actions;

use Room11\StackChat\Client\MessageEditFailureException;

class EditMessage extends Action
{
    public function getExceptionClassName(): string
    {
        return MessageEditFailureException::class;
    }

    public function processResponse($response, int $attempt): int
    {
        if ($response === 'ok') {
            $this->succeed();
            return self::SUCCESS;
        }

        $errStr = 'A JSON response with an unexpected structure was received';
        $this->logger->error($errStr, ['response' => $response]);
        $this->fail(new MessageEditFailureException($errStr));

        return self::FAILURE;
    }
}
