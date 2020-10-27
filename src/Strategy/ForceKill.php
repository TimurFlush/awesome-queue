<?php

namespace TimurFlush\Queue\Strategy;

use TimurFlush\Queue\StrategyInterface;

/**
 *
 * This strategy is necessary for the instant
 * termination of the worker after the job has been completed.
 *
 * Class ForceKill
 * @package TimurFlush\Queue\Strategy
 */
class ForceKill implements StrategyInterface
{
    public function process()
    {

    }
}
