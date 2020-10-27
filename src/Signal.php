<?php

namespace TimurFlush\Queue;

class Signal
{
    public const SOFT_KILL = SIGTERM;
    public const FORCE_KILL = SIGKILL;
    public const STOP = SIGUSR2;
    public const CONT = SIGCONT;
    public const WORKER_FREE = 'status:free';
    public const WORKER_BUSY = 'status:busy';
}
