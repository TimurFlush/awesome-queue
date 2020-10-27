<?php

namespace TimurFlush\Queue;

use TimurFlush\Queue\Entity\ReleaseCommand;

class Cursor implements CursorInterface
{
    protected ?ReleaseCommand $release;

    public function delete()
    {
        // TODO: Implement delete() method.
    }

    public function release($delay = null, int $priority = null)
    {
        $this->release = new ReleaseCommand($delay, $priority);
    }

    /**
     * Get an release options.
     *
     * @return ReleaseCommand
     */
    public function getRelease(): ReleaseCommand
    {
        return $this->release;
    }

    public function success()
    {
        // TODO: Implement success() method.
    }

    public function isSuccess(): bool
    {
        // TODO: Implement isSuccess() method.
    }

    public function isNeedDelete(): bool
    {
        // TODO: Implement isNeedDelete() method.
    }

    public function isNeedRelease(): bool
    {
        return isset($this->release);
    }
}
