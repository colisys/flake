<?php

namespace Flake\View;

if (!function_exists('flip')) {
    function flip(...$params)
    {
        return array_flip($params);
    }
}
