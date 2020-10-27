<?php

declare(strict_types = 1);

namespace TimurFlush\Queue;

use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use TimurFlush\Queue\Connection\ConnectorInterface;
use TimurFlush\Queue\Connection\PoolInterface;
use TimurFlush\Queue\Daemon\ModeInterface;
use TimurFlush\Queue\Entity\Worker as WorkerEntity;

class Daemon
{
    use WatchDog;

    /**
     * @var ConnectorInterface Connector.
     */
    protected ConnectorInterface $connector;

    /**
     * @var string Connector name.
     */
    protected string $connectorName;

    /**
     * @var ModeInterface Daemon mode.
     */
    protected ModeInterface $mode;

    /**
     * @var string Path to jobs directory.
     */
    protected string $commandPath;

    /**
     * @var callable Output handler.
     */
    protected $outputHandler;

    /**
     * @var array List of listened queues.
     */
    protected array $listenedQueues = [
        'default' => 10
    ];

    /**
     * @var WorkerEntity[][] Launched workers.
     */
    protected array $launchedWorkers = [];

    /**
     * @var string Path to worker bootstrapper file.
     */
    protected string $workerBootstrapperPath;

    /**
     * @var int Max memory limit for a worker.
     */
    protected int $maxMemoryLimitForWorker = 128;

    /**
     * @var int Max memory limit for the daemon.
     */
    protected int $maxMemoryLimitForDaemon = 128;

    /**
     * @var int Max finished jobs for a worker.
     */
    protected int $maxJobsForWorker = 100;

    /**
     * @var bool Need to pause the daemon.
     */
    protected bool $needPause = false;

    /**
     * @var bool Need to kill the daemon.
     */
    protected bool $needStop = false;

    /**
     * Daemon constructor.
     *
     * @param PoolInterface $pool           Pool of connectors.
     * @param string        $connectorName  Connector name.
     * @param string        $commandPath    Path to jobs directory.
     * @throws Exception
     */
    public function __construct(PoolInterface $pool, string $connectorName, string $commandPath)
    {
        if (!$pool->exists($connectorName)) {
            throw new Exception('The "'.$connectorName.'" was not found in passed pool.');
        }

        $this->connectorName = $connectorName;
        $this->connector = $pool->get($connectorName);
        $this->commandPath = $commandPath;
    }

    /**
     * Set a bootstrap file for creating a worker.
     *
     * @param string $pathToBootstrapper
     */
    public function setWorkerBootstrapper(string $pathToBootstrapper)
    {
        $this->workerBootstrapperPath = $pathToBootstrapper;
    }

    /**
     * Set max memory limit for the daemon.
     *
     * @param int $MB Memory limit in megabytes.
     * @return $this
     * @throws Exception
     */
    public function setMaxMemoryLimitForDaemon(int $MB)
    {
        if ($MB < 1) {
            throw new Exception('The memory limit must be greater zero');
        }

        $this->maxMemoryLimitForDaemon = $MB;
        return $this;
    }

    /**
     * Set max memory limit for a worker.
     * After limit is exceeded, worker will be restarted.
     *
     * @param int $MB Memory limit in megabytes.
     * @return $this
     * @throws Exception
     */
    public function setMaxMemoryLimitForWorker(int $MB)
    {
        if ($MB < 1) {
            throw new Exception('The memory limit must be greater zero');
        }

        $this->maxMemoryLimitForWorker = $MB;
        return $this;
    }

    /**
     * Max finished jobs for a worker.
     * After which it will be restarted.
     *
     * @param int $jobsNumber
     * @return $this
     * @throws Exception
     */
    public function setMaxJobsForWorker(int $jobsNumber)
    {
        if ($jobsNumber < 1) {
            throw new Exception('A number of jobs must be greater zero');
        }

        $this->maxJobsForWorker = $jobsNumber;
        return $this;
    }

    /**
     * Set max workers.
     *
     * @param int $number Number of workers.
     * @return $this
     * @throws Exception
     */
    public function setMaxWorkers(int $number)
    {
        if ($number <= 0) {
            throw new Exception('A number of max workers must be greater zero');
        }

        $this->maxWorkers = $number;
        return $this;
    }

    /**
     * Set queues for listening.
     *
     * @param  array $queues
     * @return $this
     * @throws Exception
     */
    public function setQueuesForListening(array $queues)
    {
        foreach ($queues as $queueName => $workersNumber) {
            if (!is_int($workersNumber) || $workersNumber <= 0) {
                throw new Exception('A number of max workers for the "'.$queueName.'" queue is invalid.');
            }
        }

        array_merge($this->listenedQueues, $queues);
        return $this;
    }

    /**
     * Get a free worker entity.
     *
     * @param string $queueName
     * @return WorkerEntity|null
     */
    protected function getFreeWorker(string $queueName): ?WorkerEntity
    {
        if ($this->isQueueAlreadyListening($queueName)) {
            foreach ($this->launchedWorkers[$queueName] as $worker) {
                if ($worker->isFree()) {
                    return $worker;
                }
            }
        }

        return null;
    }

    /**
     * Add an worker entity.
     *
     * @param string $queueName
     * @param WorkerEntity $worker
     */
    protected function pushWorker(string $queueName, WorkerEntity $worker)
    {
        if ($this->isQueueAlreadyListening($queueName)) {
            $this->launchedWorkers[$queueName][$worker->getProcessInstance()->getPid()] = $worker;
        } else {
            $this->launchedWorkers[$queueName] = [
                $worker->getProcessInstance()->getPid() => $worker
            ];
        }
    }

    /**
     * Remove a worker entity and return it.
     *
     * @param string $queueName
     * @param int    $pid       Process id of the worker.
     * @return WorkerEntity|null
     */
    protected function popWorker(string $queueName, int $pid): ?WorkerEntity
    {
        if (
            $this->isQueueAlreadyListening($queueName) &&
            isset($this->launchedWorkers[$queueName][$pid])
        ) {
            /** @var WorkerEntity $worker */
            $worker = $this->launchedWorkers[$queueName][$pid];
            unset($this->launchedWorkers[$queueName][$pid]);

            return $worker;
        }

        return null;
    }

    /**
     * Count launched workers in queue.
     *
     * @param  string $queueName
     * @return int
     */
    protected function countWorkers(string $queueName): int
    {
        if ($this->isQueueAlreadyListening($queueName)) {
            return sizeof($this->launchedWorkers[$queueName]);
        }

        return 0;
    }

    /**
     * Broadcast signal to launched workers.
     *
     * @param int         $signal     A broadcast signal.
     * @param string|null $queueName  A Queue name.
     * @param int|null    $howMany    How many workers we need to broadcast.
     */
    protected function broadcastSignal(int $signal, string $queueName = null, int $howMany = null)
    {
        foreach ($this->launchedWorkers as $selectedQueue => $workers) {
            $isQueueMatched = $selectedQueue === $queueName;

            if ($queueName === null || $isQueueMatched && $howMany === null) {
                foreach ($workers as $worker) {
                    $worker->sendSignal($signal);
                }
            } elseif ($isQueueMatched && $howMany > 0) {
                $alreadyBroadcasted = 0;

                foreach ($workers as $worker) {
                    if ($alreadyBroadcasted === $howMany) {
                        break;
                    }

                    $worker->sendSignal($signal);
                    $alreadyBroadcasted++;
                }
            }

            if ($isQueueMatched) {
                break;
            }
        }
    }

    public function setLogger()
    {

    }

    /**
     * Kill the daemon.
     */
    protected function killDaemon()
    {
        $this->broadcastSignal(Signal::STOP);

        while (true) {
            // Since we kill workers softly, we need to make sure that
            // all workers are free before we kill the demon, because
            // when we kill the daemon, all workers will be completed immediately.
            if ($this->isAllWorkersIsFree()) {
                $this->kill(SIGKILL);
            }

            sleep(1);
        }
    }

    /**
     * Determine whether all workers are free.
     *
     * @return bool
     */
    protected function isAllWorkersIsFree(): bool
    {
        $isSomeOneBusy = false;

        foreach ($this->launchedWorkers as $workers) {
            foreach ($workers as $worker) {
                $isSomeOneBusy = $worker->isBusy();
            }
        }

        return !$isSomeOneBusy;
    }

    /**
     * Determine whether to stop the demon
     *
     * @return bool
     */
    protected function needStop()
    {
        if ($this->isMemoryExceeded($this->maxMemoryLimitForDaemon)) {
            return true;
        } elseif ($this->needStop) {
            return true;
        }

        return false;
    }

    /**
     *  Determine if the specified queue exists.
     *
     * @param  string $queueName
     * @return bool
     */
    protected function isQueueAlreadyListening(string $queueName): bool
    {
        return array_key_exists($queueName, $this->launchedWorkers);
    }

    /**
     * Clean garbage.
     *
     * @param  string $queueName
     * @return void
     */
    protected function cleanGarbage(string $queueName)
    {
        if ($this->isQueueAlreadyListening($queueName)) {
            foreach ($this->launchedWorkers[$queueName] as $worker) {
                $process = $worker->getProcessInstance();

                if ($process->isTerminated()) {
                    $this->popWorker($queueName, $process->getPid());
                }
            }
        }
    }

    /**
     * Count the signals sent to workers in the specified queue.
     *
     * @param  string $queueName
     * @param  int    $signal
     * @return int
     */
    protected function countSentSignals(string $queueName, int $signal): int
    {
        $counter = 0;

        foreach ($this->launchedWorkers[$queueName] as $worker) {
            if ($worker->getLastSentSignal() === $signal) {
                $counter++;
            }
        }

        return $counter;
    }

    /**
     * Kill extra workers if they exist.
     *
     * @param  string $queueName
     * @return void
     */
    protected function killExtraWorkers(string $queueName)
    {
        $maxWorkers = $this->listenedQueues[$queueName];

        while (
            ($countWorkers = $this->countWorkers($queueName)) > $maxWorkers &&
            $this->countSentSignals($queueName, Signal::SOFT_KILL) < ($countWorkers - $maxWorkers)
        ) {
            //TODO: The cycle must be optimized to avoid extra iterations
            foreach ($this->launchedWorkers[$queueName] as $worker) {
                if ($worker->getLastSentSignal() === Signal::SOFT_KILL) {
                    continue;
                }

                $worker->sendSignal(Signal::SOFT_KILL);
                break;
            }
        }
    }

    /**
     * Run workers.
     *
     * @param  string $queueName
     * @return void
     */
    protected function runWorkers(string $queueName)
    {
        $maxWorkers = $this->listenedQueues[$queueName];

        while ($this->countWorkers($queueName) < $maxWorkers) {
            $process = new Process(
                [
                    (new PhpExecutableFinder())->find(false),
                    $this->workerBootstrapperPath,
                    $this->connectorName,
                    $queueName,
                    '--memoryLimit=' . $this->maxMemoryLimitForWorker,
                    '--jobsLimit=' . $this->maxJobsForWorker,
                ],
                $this->commandPath,
                null,
                null,
                null
            );

            $this->pushWorker($queueName, new WorkerEntity($process, $this->outputHandler));
        }
    }

    /**
     * Determine whether to stop the workers without killing them.
     *
     * @return bool
     */
    protected function needPause(): bool
    {
        //TODO: Check a Maintenance
        if ($this->needPause) {
            return true;
        } elseif (/*maintenance*/false) {
            $this->broadcastSignal(Signal::SOFT_KILL);
            return true;
        }

        return false;
    }

    /**
     * Listen queue server.
     */
    public function listen()
    {
        // Listen to the signals coming from a higher demon.
        $this->listenAsyncSignals();

        while (true) {
            // If we need to kill a demon.
            if ($this->needStop()) {
                $this->killDaemon();
            }

            if (!$this->needPause()) {
                foreach (array_keys($this->listenedQueues) as $queueName) {
                    // Delete from the memory of workers who were killed for unknown reasons
                    $this->cleanGarbage($queueName);

                    // If the number of workers is greater than necessary, we need to kill the extra workers.
                    $this->killExtraWorkers($queueName);

                    // If the number of workers is less than necessary, we need to run them.
                    $this->runWorkers($queueName);
                }
            }

            // Let's not overload the processor with extra iterations.
            sleep(1);
        }
    }

    /**
     * Set an output handler for workers.
     * @param  callable $callback
     * @return callable
     */
    public function setOutputHandler(callable $callback)
    {
        return $this->outputHandler = $callback;
    }

    /**
     * Enable async signals for the process.
     */
    protected function listenAsyncSignals()
    {
        pcntl_async_signals(true);

        pcntl_signal(Signal::SOFT_KILL, function () {
            $this->needStop = true;
        });

        // Why don't we just stop the workers if the demon stops?
        // Because we'll get a conflict in the killExtraWorkers() method.
        // This way, we will restart the workers additionally to avoid memory leaks.

        pcntl_signal(Signal::STOP, function () {
            $this->broadcastSignal(Signal::SOFT_KILL);
            $this->needPause = true;
        });

        pcntl_signal(Signal::CONT, function () {
            $this->needPause = false;
        });
    }
}
