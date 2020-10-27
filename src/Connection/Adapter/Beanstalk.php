<?php

namespace TimurFlush\Queue\Connection\Adapter;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Pheanstalk\Connection;
use Pheanstalk\Exception\ConnectionException;
use Pheanstalk\JobId;
use Pheanstalk\Pheanstalk;
use Pheanstalk\SocketFactory;
use TimurFlush\Queue\Connection\Connector;
use TimurFlush\Queue\Exception;
use TimurFlush\Queue\JobInterface;
use Pheanstalk\Contract\ResponseInterface;
use DateTimeInterface;

class Beanstalk extends Connector
{
    /**
     * @var string Host
     */
    private string $host = '127.0.0.1';

    /**
     * @var int Port
     */
    private int $port = Pheanstalk::DEFAULT_PORT;

    /**
     * @var int Connection timeout
     */
    private int $timeout = Connection::DEFAULT_CONNECT_TIMEOUT;

    /**
     * @var Pheanstalk Pheanstalk instance
     */
    private Pheanstalk $pheanstalk;

    /**
     * Beanstalk constructor.
     * @param string|null $host    Host
     * @param int|null    $port    Port
     * @param int|null    $timeout Timeout
     */
    public function __construct(string $host = null, int $port = null, int $timeout = null)
    {
        $this->host ??= $host;
        $this->port ??= $port;
        $this->timeout ??= $timeout;

        $pheanstalk = Pheanstalk::create(
            $this->host,
            $this->port,
            $this->timeout
        );

        $this->pheanstalk = $pheanstalk;
    }

    /**
     * {@inheritDoc}
     * @return $this
     */
    public function chooseQueue(string $queueName)
    {
        $this->pheanstalk->useTube($queueName);
        return $this;
    }

    /**
     * {@inheritDoc}
     * @return int Job id.
     * @throws Exception
     */
    public function pushJob(JobInterface $job): int
    {
        $job = $this->pheanstalk->put(
            serialize($job),
            $this->preparePriority($job->getPriority()),
            $this->prepareDelay($job->getDelay()),
            $job->getTimeToRun()
        );

        return $job->getId();
    }

    /**
     * {@inheritDoc}
     * @return $this
     * @throws Exception
     */
    public function releaseJob(int $jobId, $delay = null, int $priority = null)
    {
        $this->pheanstalk->release(
            new JobId($jobId),
            $this->preparePriority($priority),
            $this->prepareDelay($delay)
        );

        return $this;
    }

    /**
     * {@inheritDoc}
     * @return $this
     */
    public function deleteJob(int $jobId)
    {
        $this->pheanstalk->delete(new JobId($jobId));
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getQueueList(): array
    {
        return $this->pheanstalk->listTubes();
    }

    /**
     * Get queue statistics
     * @param string $queueName
     * @return ResponseInterface
     */
    protected function getQueueStats(string $queueName): ResponseInterface
    {
        return $this->pheanstalk->statsTube($queueName);
    }

    /**
     * {@inheritDoc}
     */
    public function getCountWorkersOnQueue(string $queueName)
    {
        $stat = $this->getQueueStats($queueName);
        return $stat['current-using'] ?? 0;
    }

    /**
     * @return Pheanstalk
     */
    public function getPheanstalk(): Pheanstalk
    {
        return $this->pheanstalk;
    }
}
