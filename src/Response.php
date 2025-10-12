<?php

namespace Flake;

class Response
{
    function status($code)
    {
        http_response_code($code);
        return $this;
    }

    function send(string $content): void
    {
        echo $content;
    }
}
