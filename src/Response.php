<?php
namespace Flake;

class Response
{
    protected int $status       = 200;
    protected array $headers    = [];
    protected bool $streaming   = true;
    protected bool $isStreaming = false;

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
        header("{$name}: {$value}");
        return $this;
    }

    /**
     * Send content
     *
     * @param string|\SplFileInfo|\SplFileObject $content
     */
    public function send($content): void
    {
        if (is_string($content)) {
            $this->doSend($content);
            return;
        }

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
            return;
        }

        throw new \InvalidArgumentException('Invalid content');
    }

    /**
     * Send content
     *
     * @param string $content
     */
    protected function doSend(string $content): void
    {
        if (! $this->streaming) {
            echo $content;
            return;
        }

        if (! $this->isStreaming) {
            ob_start();
            $this->isStreaming = true;
        }

        echo $content;
        ob_flush();
    }

    /**
     * Start streaming
     */
    public function stream(): void
    {
        $this->streaming = true;
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
     * @param string $filename
     */
    public function attachment(string $filename): void
    {
        $mime     = mime_content_type($filename);
        $filename = basename($filename);
        $this->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
        $this->type($mime);
    }

    /**
     * Send file as inline
     *
     * @param string $filename
     */
    public function download(string $filename): void
    {
        $this->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
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
    public function cookie(string $name, string $value, int $expire = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httponly = true)
    {
        setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }

    /**
     * Clear Cookie
     */
    public function clearCookie(): void
    {
        setcookie(session_name(), '', time() - 3600, '/');
    }

    public function noCache(): void
    {
        $this->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $this->header('Pragma', 'no-cache');
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
    public function type(string $type): void
    {
        $this->header('Content-Type', $type);
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
                $rmethod = new \ReflectionClass($data);
                if (! $rmethod->implementsInterface(\JsonSerializable::class)) {
                    throw new \InvalidArgumentException('Object must implement JsonSerializable interface');
                }
                $data = $data->jsonSerialize();
            }

            $this->status($status)
                ->header('Content-Type', 'application/json')
                ->send(json_encode($data, JSON_UNESCAPED_UNICODE));
        }
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
