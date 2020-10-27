<?php

namespace TimurFlush\Queue;

interface SchedulerInterface
{
    public function job();
    // // //
    public function yearly();

    public function quarterly();

    public function monthly();

    public function weekly();

    public function daily();

    public function hourly();

    public function everyMinute();

    // // //
    public function mondays();

    public function tuesdays();

    public function wednesdays();

    public function thursdays();

    public function fridays();

    public function saturdays();

    public function sundays();

    // // //
    public function onOneServer();
}
