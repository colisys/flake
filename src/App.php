<?php

namespace Flake;

use Flake\Exceptions\NotSupportHTTPMethodException;
use Flake\Persistent\Builder\SqliteBuilder;
use Flake\Persistent\Driver\SqliteDriver;
use Flake\Persistent\Facade\Facade;
use Flake\Persistent\Facade\SqliteFacade;
use Flake\Persistent\Factory;

class App
{
    public string $instanceId = '';

    public function __construct()
    {
        $this->instanceId = uniqid();

        // Use default container
        new ApplicationContext(null);
        self::path(getenv("BASE_DIR"));
        $env = make(Env::class, ['basePath' => self::path()]);

        // Initialize ApplicationContext
        if ($container = ApplicationContext::getContainer()) {
            if ($container instanceof \Flake\Container) {
                $container->set(self::class, $this);

                switch ($env->get('DATABASE_TYPE')) {
                    case 'sqlite':
                        $container->set(Facade::class, Factory::make(
                            'sqlite',
                            ['database' => basename($env->get('DATABASE_DSN') ?? ':memory:')]
                        ));
                        break;
                }
            }
        }
    }

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

        $request  = make(Request::class);
        $response = make(Response::class);
        // TODO: should we sanitize the request?
        $request->setParams($_REQUEST);

        try {
            $router = Router::buildRouter($request, $response);
            list($request, $response) = Middleware::run($request, $response);
            Router::dispatch($request, $response, $router);
        } catch (\Throwable $th) {
            if ($th instanceof NotSupportHTTPMethodException) {
                // Try to run middleware
                list($request, $response) = Middleware::run($request, $response);
                // Check middleware sent response or not
                if ($response->sent) {
                    return;
                }
            }

            dd($th);
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
