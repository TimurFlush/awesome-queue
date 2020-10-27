<?php

namespace TimurFlush\Queue;

use DateTimeInterface;

interface JobInterface
{
    /**
     * Get an identifier of the job.
     * @return int
     */
    public function getId(): int;

    /**
     * Get the job name.
     * @return string
     */
    public function getName(): string;

    /**
     * Get description of the job.
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * Get timeout of the job.
     * @return int
     */
    public function getTimeout(): int;

    /**
     * Get default queue name of the job.
     * @return string
     */
    public function getQueueName(): string;

    /**
     * Get default connection name of the job.
     * @return string
     */
    public function getConnectionName(): string;

    /**
     * Get max attempts of the job.
     * @return int
     */
    public function getMaxAttempts(): int;

    /**
     * Get attempts of the job.
     * @return int
     */
    public function getAttempts(): int;

    /**
     * Get priority of the job.
     * @return int
     */
    public function getPriority(): int;

    /**
     * Get delay of the job.
     * @return DateTimeInterface|int
     */
    public function getDelay();

    /**
     * Get time to run of the job.
     * @return int
     */
    public function getTimeToRun(): int;

    /**
     * Handle method.
     * @param CursorInterface Cursor.
     * @return mixed
     */
    public function execute(CursorInterface $cursor): CursorInterface;
}
