<?php

namespace TimurFlush\Queue\Connection;

class Pool implements PoolInterface
{
    /**
     * @var array Pool.
     */
    protected array $pool = [];

    /**
     * {@inheritDoc}
     */
    public function add(string $connectionName, ConnectorInterface $connector)
    {
        $this->pool[$connectionName] = $connector;
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $connectionName): ?ConnectorInterface
    {
        return $this->pool[$connectionName] ?? null;
    }
}
