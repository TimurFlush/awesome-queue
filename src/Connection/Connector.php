<?php

namespace TimurFlush\Queue\Connection;

use Carbon\Carbon;
use DateTimeInterface;
use TimurFlush\Queue\Exception;

abstract class Connector implements ConnectorInterface
{
    protected bool $isConnected = false;

    public const DEFAULT_PRIORITY = 100;
    public const DEFAULT_DELAY = 0;

    /**
     * Delay preparing.
     *
     * @param  DateTimeInterface|int $delay
     * @return int
     * @throws Exception If the delay argument is not integer or object of the DateTimeInterface.
     * @throws Exception If the delay argument less zero.
     */
    protected function prepareDelay($delay): int
    {
        if ($delay instanceof DateTimeInterface) {
            $delay = Carbon::now()->diffInSeconds($delay, true);
        } elseif (!is_int($delay)) {
            throw new Exception('The delay argument must be integer or object of the DateTimeInterface.');
        } elseif ($delay < 0) {
            throw new Exception('The delay argument must be greater or equal zero.');
        }

        return $delay;
    }

    /**
     * Priority preparing.
     *
     * @param  int|null $priority
     * @return int
     * @throws Exception If the priority argument less zero.
     */
    protected function preparePriority(int $priority = null)
    {
        if ($priority === null) {
            $priority = self::DEFAULT_PRIORITY;
        } elseif ($priority < 0) {
            throw new Exception('The priority argument must be greater zero.');
        }

        return $priority;
    }

    public function isConnected(): bool
    {
        return $this->isConnected;
    }
}
