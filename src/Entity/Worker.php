<?php

namespace TimurFlush\Queue\Entity;

use Symfony\Component\Process\Process;

class Worker
{
    /**
     * @var bool Is this worker is free.
     */
    protected bool $isFree = true;

    /**
     * @var int A number of finished jobs by this worker.
     */
    protected int $finishedJobs = 0;

    /**
     * @var Process Symfony process instance.
     */
    protected Process $process;

    /**
     * @var int A PID of the worker process.
     */
    protected int $PID;

    /**
     * @var null|callable An output handler.
     */
    protected $outputHandler;

    /**
     * @var int|null Last signal.
     */
    protected ?int $lastSignal = null;

    public const STATUS_FREE = 'status:isFree';
    public const STATUS_BUSY = 'status:isBusy';

    /**
     * Worker constructor.
     *
     * @param Process       $symfonyProcess
     * @param callable|null $outputHandler
     */
    public function __construct(Process $symfonyProcess, ?callable $outputHandler)
    {
        $this->outputHandler = $outputHandler;

        $symfonyProcess->start(function ($type, $line) {
            // Determine a type of an output message
            switch ($type) {
                case Process::OUT:
                    $type = 'info';
                    break;

                case Process::ERR:
                    $type = 'error';
                    break;
            }

            // The "free/busy" mechanism
            if ($type === 'info' && in_array($line, [self::STATUS_FREE, self::STATUS_BUSY])) {
                if ($line === self::STATUS_FREE) {
                    $this->markAsFree();
                } elseif ($line === self::STATUS_BUSY) {
                    $this->markAsBusy();
                }

                return;
            }

            // Call the user handler
            if (is_callable($this->outputHandler)) {
                call_user_func($this->outputHandler, $type, $line);
            }
        });

        $this->process = $symfonyProcess;
        $this->PID = $symfonyProcess->getPid();
    }

    /**
     * Determine if this worker is free.
     *
     * @return bool
     */
    public function isFree(): bool
    {
        return $this->isFree;
    }

    /**
     * Determine if this worker is busy.
     *
     * @return bool
     */
    public function isBusy(): bool
    {
        return !$this->isFree();
    }

    /**
     * Mark this worker as busy.
     *
     */
    public function markAsBusy()
    {
        $this->isFree = false;
    }

    /**
     * Mark this worker as free.
     *
     */
    public function markAsFree()
    {
        $this->isFree = true;
    }

    /**
     * Increment the finished jobs counter.
     *
     */
    public function incrementFinishedJobs()
    {
        $this->finishedJobs++;
    }

    /**
     * Get a number of finished jobs.
     *
     * @return int
     */
    public function countFinishedJobs(): int
    {
        return $this->finishedJobs;
    }

    /**
     * Broadcast a signal to the worker.
     * @param int $signal
     */
    public function sendSignal(int $signal = SIGTERM)
    {
        $process = $this->getProcessInstance();

        if ($process->isRunning()) {
            $process->signal($signal);
            $this->lastSignal = $signal;
        }
    }

    /**
     * Get a last sent signal.
     *
     * @return int|null
     */
    public function getLastSentSignal(): ?int
    {
        return $this->lastSignal;
    }

    /**
     * Get a PID of the worker process.
     *
     * @return int
     */
    public function getPID(): int
    {
        return $this->getPID();
    }

    /**
     * Get a process instance.
     *
     * @return Process
     */
    public function getProcessInstance(): Process
    {
        return $this->process;
    }
}
