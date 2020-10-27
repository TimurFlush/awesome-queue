<?php

use Phalcon\Helper\Arr;
use TimurFlush\Queue\Connection\Adapter\Beanstalk;
use TimurFlush\Queue\Daemon;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\InputStream;
use TimurFlush\Queue\Connection\Pool;

require_once __DIR__ . '/../vendor/autoload.php';

class A {
    public string $a;

    public function __construct(array $a = [])
    {
        $this->a = $this->a ?: $a['kek'];
    }
}

new A();