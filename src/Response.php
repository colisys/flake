<?php
namespace Flake;

use ReflectionClass;

class Response
{
    protected int $status     = 200;
    protected array $headers  = [];
    protected bool $streaming = true;

    /**
     * Set HTTP Status Code
     *
     * @param int $code
     * @return self
     */
    public function status($code = 200)
    {
        $this->status = $code;
        http_response_code($code);
        return $this;
    }

    /**
     * Set HTTP Header
     *
     * @param string $name
     * @param string $value
     * @return self
     */
    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Send raw content
     *
     * @param string $content
     */
    public function send(string $content): void
    {
        // Send any accumulated headers
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
        echo $content;
    }

    /**
     * Send 204 No Content response
     */
    public function truncate(): void
    {
        $this->status(204)->send('');
    }

    /**
     * Redirect to a URL
     *
     * @param string $url
     */
    public function redirect(string $url, int $code = 302): void
    {
        $this->status($code)
            ->header('Location', $url)
            ->send('Redirecting...');
    }

    /**
     * Send JSON response
     *
     * @param array $data
     */
    public function json(array | object $data, int $status = 200): void
    {
        if ($this->streaming) {
            $this->streaming = false;

            if (is_object($data)) {
                $rmethod = new ReflectionClass($data);
                if (! $rmethod->implementsInterface(\JsonSerializable::class)) {
                    throw new \InvalidArgumentException('Object must implement JsonSerializable interface');
                }
                $data = $data->jsonSerialize();
            }

            // Send any accumulated headers
            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }

            $this->status($status)
                ->header('Content-Type', 'application/json')
                ->send(json_encode($data, JSON_UNESCAPED_UNICODE));
        }
    }
}
