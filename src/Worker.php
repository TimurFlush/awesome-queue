<?php

namespace TimurFlush\Queue;

use Phalcon\Di;
use TimurFlush\Queue\Connection\ConnectorInterface;
use TimurFlush\Queue\Connection\PoolInterface;
use TimurFlush\Queue\Entity\Worker as WorkerEntity;

class Worker implements WorkerInterface
{
    use WatchDog;

    /**
     * @var string|null Queue name.
     */
    protected ?string $queueName = null;

    /**
     * @var ConnectorInterface Connector.
     */
    protected ConnectorInterface $connector;

    /**
     * @var int Memory limit in megabytes.
     */
    protected int $memoryLimit = 128;

    /**
     * @var int Number of max jobs.
     */
    protected int $maxJobs = 100;

    /**
     * @var int Number of completed tasks.
     */
    protected int $completedTasks = 0;

    /**
     * @var bool Is stop need.
     */
    protected bool $needStop = false;

    /**
     * @var bool Is pause need.
     */
    protected bool $needPause = false;

    /**
     * @var resource An output stream.
     */
    protected $outputStream;

    /**
     * Worker constructor.
     *
     * @param  PoolInterface $pool
     * @param  string        $connectorName
     * @throws Exception
     */
    public function __construct(PoolInterface $pool, string $connectorName)
    {
        if (!$pool->exists($connectorName)) {
            throw new Exception('The passed connector name not found in the pool.');
        }

        $this->connector = $pool->get($connectorName);
    }

    /**
     * {@inheritDoc}
     * @return $this
     * @throws Exception
     */
    public function setMemoryLimit(int $MB)
    {
        if ($MB < 1) {
            throw new Exception('The memory limit must be greater zero');
        }

        $this->memoryLimit = $MB;
        return $this;
    }

    /**
     * {@inheritDoc}
     * @return $this
     * @throws Exception
     */
    public function setMaxJobs(int $number)
    {
        if ($number < 1) {
            throw new Exception('The number of max jobs must be greater zero');
        }

        $this->maxJobs = $number;
        return $this;
    }

    /**
     * {@inheritDoc}
     * @return $this
     * @throws Exception
     */
    public function setQueueName(string $queueName)
    {
        if (empty($queueName)) {
            throw new Exception('Queue name must be not empty');
        }

        $this->queueName = $queueName;
        return $this;
    }

    /**
     * {@inheritDoc}
     * @return $this
     */
    public function setConnector(ConnectorInterface $connector)
    {
        $this->connector = $connector;
        return $this;
    }

    /**
     * Determine whether to stop the worker.
     *
     * @return bool
     */
    protected function needStop()
    {
        if ($this->isMemoryExceeded($this->memoryLimit)) {
            return true;
        } elseif ($this->needStop) {
            return true;
        } elseif ($this->completedTasks >= $this->maxJobs) {
            return true;
        }

        return false;
    }

    protected function listenAsyncSignals()
    {
        pcntl_async_signals(true);

        pcntl_signal(Signal::SOFT_KILL, function () {
            $this->needStop = true;
        });

        pcntl_signal(Signal::STOP, function () {
            $this->needPause = true;
        });

        pcntl_signal(Signal::CONT, function () {
            $this->needPause = false;
        });
    }

    /**
     * Send status to the daemon.
     *
     * @param string $status
     */
    protected function sendStatus(string $status)
    {
        fwrite(STDOUT, $status);
    }

    /**
     * {@inheritDoc}
     */
    public function run()
    {
        $this->listenAsyncSignals();
        $this->connector->chooseQueue($this->queueName);

        while (true) {
            if ($this->needStop()) {
                break;
            }

            $job = $this->connector->getNextJob();


            if ($job instanceof JobInterface) {
                $this->sendStatus(Signal::WORKER_BUSY);

                $cursor = $job->execute(new Cursor());

                if ($cursor->isNeedRelease()) {
                    $releaseOptions = $cursor->
                    $this->connector->releaseJob($job->getId(), '');
                } elseif ($cursor->isNeedDelete() || $cursor->isSuccess()) {
                    $this->connector->deleteJob($job->getId());
                }

                $this->completedTasks++;
                $this->sendStatus(Signal::WORKER_FREE);
            }

            continue;
        }
    }
}
