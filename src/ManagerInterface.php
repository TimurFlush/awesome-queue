<?php

namespace TimurFlush\Queue;

interface ManagerInterface
{
    public function dispatch(JobInterface $job);
}
