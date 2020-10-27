<?php

namespace TimurFlush\Queue\Connection;

use Carbon\CarbonInterface;
use TimurFlush\Queue\JobInterface;

interface ConnectorInterface
{
    /**
     * Choose queue for next commands.
     *
     * @param  string $queueName
     * @return mixed
     */
    public function chooseQueue(string $queueName);

    /**
     * Push new job.
     *
     * @param  JobInterface $job
     * @return int
     */
    public function pushJob(JobInterface $job): int;

    /**
     * Release a job.
     *
     * @param  int                    $jobId
     * @param  int|\DateTimeInterface $delay
     * @param  int                    $priority
     * @return mixed
     */
    public function releaseJob(int $jobId, $delay, int $priority);

    /**
     * Get queue list.
     *
     * @return array
     */
    public function getQueueList(): array;

    /**
     * Get count workers on queue.
     *
     * @param  string $queueName
     * @return mixed
     */
    public function getCountWorkersOnQueue(string $queueName);

    /**
     * Get next job and reserve.
     *
     * @return null|JobInterface
     */
    public function getNextJob(): ?JobInterface;

    /**
     * Delete a job.
     *
     * @param  int    $jobId
     * @return mixed
     */
    public function deleteJob(int $jobId);
}
