<?php

namespace TimurFlush\Queue;

use DateTimeInterface;

interface CursorInterface
{
    /**
     * Determine if the task should be deleted.
     *
     * @return bool
     */
    public function isNeedDelete(): bool;

    /**
     * Determine if the task needs to be released.
     *
     * @return bool
     */
    public function isNeedRelease(): bool;

    /**
     * Determine whether the task is successfully completed.
     *
     * @return bool
     */
    public function isSuccess(): bool;

    /**
     * Delete the job from queue.
     */
    public function delete();

    /**
     * Release the job.
     *
     * @param  DateTimeInterface|int $delay
     * @param  int                   $priority
     */
    public function release($delay = null, int $priority = null);

    //public function getReleaseEntity():

    /**
     * Mark current job as success.
     */
    public function success();
}
