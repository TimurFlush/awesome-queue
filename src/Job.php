<?php

namespace TimurFlush\Queue;

use Phalcon\Helper\Arr;
use Serializable;
use DateTimeInterface;
use TimurFlush\Queue\Connection\Connector;

/**
 * Class Job
 * @package TimurFlush\Queue
 * @author  Timur Flush
 * @license BSD 3-Clause
 * @method  initialize() Method which will be call in parent constructor.
 */
abstract class Job implements JobInterface, Serializable
{
    /**
     * @var string|null Job name.
     */
    protected ?string $name = null;

    /**
     * @var string|null Description of the job.
     */
    protected ?string $description = null;

    /**
     * @var string|null Default queue name of the job.
     */
    protected ?string $queueName = null;

    /**
     * @var string|null Default connection name of the job.
     */
    protected ?string $connectionName = null;

    /**
     * @var int Timeout of the job.
     */
    protected int $timeout;

    /**
     * @var int Max attempts of the job.
     */
    protected int $maxAttempts = 1;

    /**
     * @var int Attempts of the job.
     */
    protected int $attempts = 0;

    /**
     * @var int Priority of the job.
     */
    protected int $priority = Connector::DEFAULT_PRIORITY;

    /**
     * @var DateTimeInterface|null Delay of the job.
     */
    protected $delay = Connector::DEFAULT_DELAY;

    /**
     * @var int Time to run of the job.
     */
    protected int $timeToRun = 0;

    /**
     * Job constructor.
     */
    final public function __construct()
    {
        if (method_exists($this, 'initialize')) {
            $this->initialize();
        }
    }

    /**
     * Set name of the job.
     *
     * @param  string $name
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set description of the job.
     *
     * @param  string $description
     * @return $this
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set timeout of the job.
     *
     * @param  int   $seconds
     * @return $this
     */
    public function setTimeout(int $seconds)
    {
        $this->timeout = $seconds;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Set default queue name of the job.
     *
     * @param  string $queueName
     * @return $this
     */
    public function setQueueName(string $queueName)
    {
        $this->queueName = $queueName;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getQueueName(): string
    {
        return $this->queueName;
    }

    /**
     * Set default connection name of the job.
     *
     * @param  string $connectionName
     * @return $this
     */
    public function setConnectionName(string $connectionName)
    {
        $this->connectionName = $connectionName;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getConnectionName(): string
    {
        return $this->connectionName;
    }

    /**
     * Set max attempts of the job.
     *
     * @param  int       $maxAttempts Max attempts.
     * @throws Exception
     */
    public function setMaxAttempts(int $maxAttempts)
    {
        if ($maxAttempts < 1) {
            throw new Exception('Maximum attempts should be over zero.');
        }

        $this->maxAttempts = $maxAttempts;
    }

    /**
     * {@inheritDoc}
     */
    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    /**
     * Increment attempts counter of the job.
     *
     * @return int
     */
    public function incrementAttempts(): int
    {
        return $this->attempts++;
    }

    /**
     * {@inheritDoc}
     */
    public function getAttempts(): int
    {
        return $this->attempts;
    }

    /**
     * Set priority of the job.
     *
     * @param  int $priority
     * @return int
     */
    public function setPriority(int $priority)
    {
        $this->priority = $priority;
        return $this->priority;
    }

    /**
     * {@inheritDoc}
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Set delay for the job.
     *
     * @param  int       $delay
     * @throws Exception If the delay argument less zero.
     */
    public function setDelay(int $delay)
    {
        if ($delay < 0) {
            throw new Exception('The delay argument must be greater zero.');
        }

        $this->delay = $delay;
    }

    /**
     * {@inheritDoc}
     */
    public function getDelay(): int
    {
        return $this->delay;
    }

    /**
     * Set time to run for the job.
     *
     * @param int $timeToRun
     */
    public function setTimeToRun(int $timeToRun)
    {
        $this->timeToRun = $timeToRun;
    }

    /**
     * {@inheritDoc}
     */
    public function getTimeToRun(): int
    {
        return $this->timeToRun;
    }

    /**
     * Job serialization.
     *
     * @return string
     */
    public function serialize()
    {
        //Ira, i love you & i hope that you will be love me too
        $properties = get_object_vars($this);
        return serialize(
            array_filter($properties, fn($property) => !is_callable($property) && !is_object($property))
        );
    }

    /**
     * Job unserialization.
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $unserialized = unserialize($serialized);

        foreach ($unserialized as $property => $value) {
            # Here we exclude extra properties if job has been changed
            if (property_exists($this, $property)) {
                $this->{$property} = $value;
            }
        }
    }
}
