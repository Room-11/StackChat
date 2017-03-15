<?php declare(strict_types = 1);

namespace Room11\StackExchangeChatClient\Client\Actions;

class MoveMessageAction extends Action
{
    public function processResponse($response, int $attempt): int
    {
        if (is_int($response)) {
            $this->succeed();
            return self::SUCCESS;
        }

        $errStr = 'A JSON response that I don\'t understand was received';
        $this->logger->error($errStr, ['response' => $response]);
        $this->fail(new MessageMoveFailureException($errStr));

        return self::FAILURE;
    }
}
