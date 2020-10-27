<?php

namespace TimurFlush\Queue;

use TimurFlush\Queue\Connection\ConnectorInterface;

class Manager implements ManagerInterface
{
    /**
     * @var ConnectorInterface Connector
     */
    protected ConnectorInterface $connector;

    public function __construct(ConnectorInterface $connector)
    {
        $this->connector = $connector;
    }

    public function dispatch(JobInterface $job)
    {
        $this->connector->pushJob($job);
    }
}
