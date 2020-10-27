<?php

namespace TimurFlush\Queue;

use TimurFlush\Queue\Connection\ConnectorInterface;

interface WorkerInterface
{
    /**
     * Set queue name.
     * @param string $queueName
     * @return mixed
     */
    public function setQueueName(string $queueName);

    /**
     * Set connector.
     * @param ConnectorInterface $connector
     * @return mixed
     */
    public function setConnector(ConnectorInterface $connector);

    /**
     * Set memory limit.
     *
     * @param  int $MB Memory limit in megabytes.
     */
    public function setMemoryLimit(int $MB);

    /**
     * Set max jobs limit.
     *
     * @param  int   $number A number of max jobs.
     * @return mixed
     */
    public function setMaxJobs(int $number);

    /**
     * Run worker.
     */
    public function run();
}
