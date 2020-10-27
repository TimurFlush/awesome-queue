<?php

namespace TimurFlush\Queue\Strategy;

use TimurFlush\Queue\StrategyInterface;

/**
 *
 * This strategy is necessary to restart
 * the worker after N number of completed or failed tasks.
 *
 * Class ForceRestart
 * @package TimurFlush\Queue\Strategy
 */
class ForceRestart implements StrategyInterface
{
    /**
     * @var int Number of tasks after which the worker will be restarted.
     */
    protected int $maxJobsForWorker = 15;

    /**
     * ForceRestart constructor.
     * @param int|null $maxJobsForWorker
     */
    public function __construct(int $maxJobsForWorker = null)
    {
        $this->maxJobsForWorker ??= $maxJobsForWorker;
    }

    public function process()
    {
        // TODO: Implement process() method.
    }
}
