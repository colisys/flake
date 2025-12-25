<?php

namespace Flake\Persistent\Trait;

trait Timestamp
{
    public static ?string $created_at = 'created_at';
    public static ?string $updated_at = 'updated_at';
}
