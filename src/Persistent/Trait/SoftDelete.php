<?php

namespace Flake\Persistent\Trait;

trait SoftDelete
{
    public $deleted_at = null;

    public function delete()
    {
        $this->deleted_at = date('Y-m-d H:i:s');
        return $this->save();
    } 
}