<?php
namespace Flake;

class Response
{
    protected int $status    = 200;
    protected array $headers = [];

    public function status($code)
    {
        $this->status = $code;
        http_response_code($code);
        return $this;
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        header("{$name}: {$value}");
        return $this;
    }

    function send(string $content): void
    {
        // Send any accumulated headers
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
        echo $content;
    }

    public function json(array | object $data, int $status = 200): void
    {
        $this->status($status)
            ->header('Content-Type', 'application/json')
            ->send(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}
