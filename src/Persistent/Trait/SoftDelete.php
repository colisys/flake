<?php

namespace Flake\Persistent\Trait;

use Flake\Persistent\Event\BeforeDelete;
use Psr\EventDispatcher\EventDispatcherInterface;

use function Flake\make;

trait SoftDelete
{
    public static ?string $deleted_at = 'deleted_at';

    public function restore()
    {
        $this->{$this->deleted_at} = null;
        return $this->save();
    }

    public function isDeleted(): bool
    {
        return !is_null($this->{$this->deleted_at});
    }

    public function delete()
    {
        if (make(EventDispatcherInterface::class)?->dispatch(new BeforeDelete($this)) === false)
            return true;

        $this->{$this->deleted_at} = date('Y-m-d H:i:s');
        return $this->save();
    }

    public function find($id = null)
    {
        return parent::find($id)->where($this->deleted_at, null);
    }
}
