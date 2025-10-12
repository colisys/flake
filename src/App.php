<?php

namespace Flake;

class App
{
    public static function run()
    {
        try {
            [$request, $response] = Middleware::run();
            Router::dispatch($request, $response);
        } catch (\Throwable $th) {
            // Error message to console
            error_log("Error captured, code: " . $th->getCode() . ', message: ' . $th->getMessage() . PHP_EOL .
                '== Trace Begin == ' . PHP_EOL . PHP_EOL . $th->getTraceAsString() . PHP_EOL . PHP_EOL . '== Trace End ==');
            $response?->status(500)
                ->send("Internal Server Error");
            die();
        }
    }
}
