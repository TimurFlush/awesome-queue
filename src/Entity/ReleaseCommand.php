<?php

declare(strict_types = 1);

namespace TimurFlush\Queue\Entity;

use DateTimeInterface;

class ReleaseCommand
{
    /**
     * @var DateTimeInterface|int|null Delay.
     */
    protected $delay;

    /**
     * @var null|int Priority.
     */
    protected ?int $priority;

    /**
     * ReleaseCommand constructor.
     *
     * @param null|DateTimeInterface|int $delay
     * @param null|int                   $priority
     */
    public function __construct($delay = null, int $priority = null)
    {
        $this->delay = $delay;
        $this->priority = $priority;
    }

    /**
     * Get a priority.
     *
     * @return int|null
     */
    public function getPriority(): ?int
    {
        return $this->priority;
    }

    /**
     * Get a delay.
     *
     * @return DateTimeInterface|int|null
     */
    public function getDelay()
    {
        return $this->delay;
    }
}
