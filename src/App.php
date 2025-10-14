<?php
namespace Flake;

use Flake\Exceptions\NotSupportHTTPMethodException;

class App
{
    /**
     * Run the app
     *
     * @return void
     */
    public static function run()
    {
        // Ensure initialized (lazy-init pattern)
        if (! self::$basePath) {
            self::init(getcwd());
        }

        $request  = new Request();
        $response = new Response();
        // TODO: should we sanitize the request?
        $request->setParams($_REQUEST);

        try {
            $router = Router::buildRouter($request, $response);
            Middleware::run($request, $response);
            Router::dispatch($request, $response, $router);
        } catch (\Throwable $th) {
            if ($th instanceof NotSupportHTTPMethodException) {
                // Try to run middleware
                Middleware::run($request, $response);
                // Check middleware sent response or not
                if ($response->sent) {
                    return;
                }
            }

            error_log($th);
            $response->status(500)->send('Internal Server Error');
        }
    }

    protected static string $basePath = '';

    /**
     * Initialize the app
     *
     * @param string $basePath
     * @return static
     */
    public static function init(string $basePath = __DIR__): static
    {
        self::$basePath = rtrim($basePath, '/');
        return new static();
    }

    /**
     * Get or set the base path
     *
     * @param string|null $path
     * @return string
     */
    public static function path(?string $path = null): string
    {
        if ($path) {
            self::$basePath = rtrim($path, '/');
        }

        return self::$basePath;
    }
}
