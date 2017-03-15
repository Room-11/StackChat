<?php declare(strict_types = 1);

namespace Room11\StackChat\Client;

use Amp\Artax\HttpClient;
use Amp\Artax\Response as HttpResponse;
use Amp\Pause;
use Amp\Promise;
use Ds\Queue;
use ExceptionalJSON\DecodeErrorException as JSONDecodeErrorException;
use Psr\Log\LoggerInterface as Logger;
use Room11\StackChat\Client\Actions\Action;
use function Amp\resolve;

class ActionExecutor
{
    private $httpClient;
    private $logger;

    /**
     * @var Queue[]
     */
    private $actionQueues = [];

    /**
     * @var bool[]
     */
    private $runningLoops = [];

    public function __construct(HttpClient $httpClient, Logger $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    private function executeAction(Action $action)
    {
        $exceptionClass = $action->getExceptionClassName();

        if (!$action->isValid()) {
            $action->fail(new $exceptionClass('Action no longer valid at point of execution'));
            return;
        }

        $attempt = 0;

        while ($attempt++ < $action->getMaxAttempts()) {
            /** @var HttpResponse $response */
            $this->logger->debug('Post attempt ' . $attempt);
            $response = yield $this->httpClient->request($action->getRequest());
            $this->logger->debug('Got response from server: ' . $response->getBody());

            if ($response->getStatus() === 409) {
                try {
                    $delay = $this->getBackOffDelay($response->getBody());
                } catch (\InvalidArgumentException $e) {
                    $errStr = 'Got a 409 response to an Action request that could not be decoded as a back-off delay';
                    $this->logger->error($errStr, ['response_body' => $response->getBody()]);
                    $action->fail(new $exceptionClass($errStr, $e->getCode(), $e));
                    return;
                }

                $this->logger->debug("Backing off chat action execution for {$delay}ms");
                yield new Pause($delay);

                continue;
            }

            if ($response->getStatus() !== 200) {
                $errStr = 'Got a ' . $response->getStatus() . ' response to an Action request';
                $this->logger->debug($errStr, ['request' => $action->getRequest(), 'response' => $response]);
                $action->fail(new $exceptionClass($errStr));
                return;
            }

            try {
                $decoded = json_try_decode($response->getBody(), true);
            } catch (JSONDecodeErrorException $e) {
                $errStr = 'A response that could not be decoded as JSON was received'
                    . ' (JSON decode error: ' . $e->getMessage() . ')';
                $this->logger->debug($errStr, $response->getBody());
                $action->fail(new $exceptionClass($errStr, $e->getCode(), $e));
                return;
            }

            $result = $action->processResponse($decoded, $attempt);

            if ($result < 1) {
                return;
            }

            if ($attempt >= $action->getMaxAttempts()) {
                break;
            }

            $this->logger->debug("Backing off chat action execution for {$result}ms");
            yield new Pause($result);
        }

        $this->logger->debug('Executing an action failed after ' . $action->getMaxAttempts() . ' attempts and I know when to quit');
    }

    private function getBackOffDelay(string $body): int
    {
        if (!preg_match('/You can perform this action again in (\d+) second/i', $body, $matches)) {
            throw new \InvalidArgumentException;
        }

        return (int)(($matches[1] + 1.1) * 1000);
    }

    private function executeActionsFromQueue(string $key): \Generator
    {
        $this->runningLoops[$key] = true;
        $this->logger->debug("Starting action executor loop for {$key}");

        $queue = $this->actionQueues[$key];

        while ($queue->count() > 0) {
            try {
                yield from $this->executeAction($queue->pop());
            } catch (\Throwable $e) {
                $this->logger->debug(
                    "Unhandled exception while executing ChatAction for {$key}: {$e->getMessage()}",
                    ['exception' => $e->__toString()]
                );
            }
        }

        $this->logger->debug("Action executor loop terminating for {$key}");
        $this->runningLoops[$key] = false;
    }

    public function enqueue(Action $action): Promise
    {
        $key = $action->getRoom()->getIdentifier()->getIdentString();

        if (!isset($this->actionQueues[$key])) {
            $this->actionQueues[$key] = new Queue;
        }

        $this->actionQueues[$key]->push($action);

        if (empty($this->runningLoops[$key])) {
            resolve($this->executeActionsFromQueue($key));
        }

        return $action->promise();
    }
}
