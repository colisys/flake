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
            ->send(json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Render a PHP view file.
     *
     * @param string $view Path relative to project root (without .php)
     * @param array|object $data Data to be extracted into view
     */
    public function render(string $view, array|object $data = []): void
    {
        $base = dirname(__DIR__); // your framework root (e.g., src/../)
        $viewPath = $base . '/' . ltrim($view, '/') . '.php';

        if (!file_exists($viewPath)) {
            $this->status(404)->send("View not found: {$viewPath}");
            return;
        }

        // Convert object to array for extract()
        if (is_object($data)) {
            $data = (array)$data;
        }

        extract($data, EXTR_SKIP);

        ob_start();
        include $viewPath;
        $output = ob_get_clean();

        $this->status(200);
        header('Content-Type: text/html; charset=utf-8');
        echo $output;
    }
}
