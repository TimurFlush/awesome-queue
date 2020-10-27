<?php

use TimurFlush\Queue\Job;
use TimurFlush\Queue\CursorInterface;

class EmailJob extends \TimurFlush\Queue\Job
{
    public function initialize()
    {
        $this->setName('Email');
        $this->setDescription('Job need to send mails for subscribers.');
    }


    public function execute(CursorInterface $cursor): CursorInterface
    {
        $date = date('Y');

        if ($date === '2019') {
            return $cursor->release();
        }

        mb_send_mail('flush02@yandex.ru', 'Happy new year!', 'С новым годом тя!');

        return $cursor->success();
    }
}
