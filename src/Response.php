<?php

namespace Flake;

class Response
{
    protected int $status       = 200;
    protected array $headers    = [];
    protected bool $streaming   = true;
    protected bool $isStreaming = false;
    public bool $sent           = false;

    /**
     * Set HTTP Status Code
     *
     * @param int $code
     * @return self
     */
    public function status($code = 200): static
    {
        $this->status = $code;
        http_response_code($code);
        return $this;
    }

    /**
     * Set HTTP Header
     *
     * @param string|array<string,string> $name
     * @param mixed $value
     * @param bool $replace
     * @return self
     */
    public function header($name, $value, bool $replace = true): static
    {
        if (is_array($name)) {
            foreach ($name as $key => $value) {
                $this->header($key, $value);
            }
            return $this;
        }

        // For multiple headers with same name, like Set-Cookie
        if (isset($this->headers[$name])) {
            if (!is_array($this->headers[$name]))
                $this->headers[$name] = [$this->headers[$name]];
            if ($replace)
                $this->headers[$name] = [];
            $this->headers[$name][] = $value;
            header("{$name}: {$value}", $replace);
        } else {
            $this->headers[$name] = $value;
            header("{$name}: {$value}", $replace);
        }

        return $this;
    }

    /**
     * Send content
     *
     * @param mixed $content
     */
    public function send($content): static
    {
        if ($content instanceof \SplFileInfo) {
            if ($this->isStreaming) {
                throw new \RuntimeException('Cannot send content while streaming');
            }
            $content = $content->openFile();
        }

        if ($content instanceof \SplFileObject) {
            while ($content->eof() === false) {
                $data = $content->fread(1024);
                if ($data === false) {
                    break;
                }
                $this->doSend($data);
            }
            unset($content, $data);
            return $this;
        }

        if (is_array($content) || is_object($content)) {
            $this->json($content);
            return $this;
        }

        $this->doSend($content);
        $this->sent = true;
        return $this;
    }

    /**
     * Get HTTP Header
     * 
     * @param string $name
     * @return array<array-key,string>
     */
    protected function getHeader(string $name)
    {
        return array_filter($this->headers, function ($key) use ($name) {
            return strtolower($key) === strtolower($name);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Send content
     *
     * @param string $content
     */
    protected function doSend($content): static
    {
        if (! $this->streaming) {
            echo $content;
            return $this;
        }

        if (! $this->isStreaming) {
            ob_start();
            $this->isStreaming = true;
        }

        echo $content;
        if (! $this->isStreaming)
            ob_flush();
        return $this;
    }

    /**
     * Start streaming
     */
    public function stream(): static
    {
        $this->streaming = true;
        return $this;
    }

    /**
     * End streaming
     */
    public function end(): void
    {
        if (! $this->isStreaming) {
            return;
        }

        ob_end_flush();
        $this->isStreaming = false;
    }

    /**
     * Send file as attachment
     * 
     * This will also set Content-Type header based on file extension, and stream the file,
     * i.e. as attachment, no output buffering.
     *
     * @param string $filename
     */
    public function attachment(string $filename): void
    {
        $this->sent = true;
        $mime     = mime_content_type($filename);
        $filename = basename($filename);
        $this->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
        $this->type($mime);
        $this->send(file_get_contents($filename));
    }

    /**
     * Send file as inline
     * 
     * This will write the file to the output buffer and set the content type to the mime type of the file.
     *
     * @param string $filename
     */
    public function download(string $filename): void
    {
        $this->sent = true;
        $basename = basename($filename);
        $this->header('Content-Disposition', "attachment; filename=\"{$basename}\"");
        $this->send(file_get_contents($filename));
    }

    /**
     * Set Cookie
     *
     * @param string $name
     * @param string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httponly
     */
    public function cookie(
        string $name,
        string $value,
        int $expire = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httponly = true
    ): static {
        setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
        return $this;
    }

    /**
     * Clear Cookie
     */
    public function clearCookie(): void
    {
        setcookie(session_name(), '', time() - 3600, '/');
    }

    /**
     * Disable caching
     */
    public function noCache(): static
    {
        $this->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $this->header('Pragma', 'no-cache');
        return $this;
    }

    /**
     * Send 204 No Content response
     */
    public function truncate(): void
    {
        $this->sent = true;
        $this->status(204)->send('');
    }

    /**
     * Send 301 Moved Permanently response
     *
     * @param string $url
     */
    public function location(string $url): void
    {
        header("Location: {$url}");
    }

    /**
     * Set Content Type
     *
     * @param string $type
     */
    public function type(string $type): static
    {
        $this->header('Content-Type', $type);
        return $this;
    }

    /**
     * Send JSON response
     *
     * @param array $data
     * @param int $status
     * @throws \InvalidArgumentException
     */
    public function json(array | object $data, int $status = 200): void
    {
        if ($this->streaming) {
            $this->streaming = false;

            // Should check if $data implement JsonSerializable interface
            if (is_object($data)) {
                $rmethod = new \ReflectionClass($data);
                if (! $rmethod->implementsInterface(\JsonSerializable::class)) {
                    throw new \InvalidArgumentException('Object must implement JsonSerializable interface');
                }
                $data = $data->jsonSerialize();
            }

            $this->status($status)
                ->header('Content-Type', 'application/json')
                ->send(json_encode($data, JSON_UNESCAPED_UNICODE));
            $this->sent = true;
        }
    }

    /**
     * Redirect to another URL
     *
     * @param string $url
     * @param int $status HTTP status code (optional, default 302)
     * @return void
     */
    public function redirect(string $url, int $status = 302): void
    {
        http_response_code($status);
        header("Location: {$url}");
        exit; // Stop further execution
    }
}
