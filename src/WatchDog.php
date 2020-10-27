<?php

namespace TimurFlush\Queue;

trait WatchDog
{
    /**
     * Is memory exceeded.
     *
     * @param int $limit Memory limit in megabytes.
     * @return bool
     */
    protected function isMemoryExceeded(int $limit)
    {
        return (memory_get_usage(true) / 1024 / 1024) >= $limit;
    }

    /**
     * Kill current process.
     * @param int $code A UNIX code.
     */
    protected function kill(int $code = SIGKILL)
    {
        if (extension_loaded('posix')) {
            posix_kill(getmypid(), SIGKILL);
        }

        exit($code);
    }
}
