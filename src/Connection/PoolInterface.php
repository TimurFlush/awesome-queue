<?php

namespace TimurFlush\Queue\Connection;

interface PoolInterface
{
    /**
     * Add a connector.
     * @param string             $name      Connector name.
     * @param ConnectorInterface $connector Connector instance.
     */
    public function add(string $name, ConnectorInterface $connector);

    /**
     * Get a connector by his name.
     * @param string $name Connector name.
     * @return ConnectorInterface|null
     */
    public function get(string $name): ?ConnectorInterface;

    /**
     * Determines the existence of a connector by its name.
     * @param string $name Connector name.
     * @return bool
     */
    public function exists(string $name): bool;
}
