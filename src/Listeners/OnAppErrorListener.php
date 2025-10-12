<?php
namespace Flake\Listeners;

use Flake\Contract\EventListenerInterface;

class OnAppErrorListener implements EventListenerInterface
{
    public static function handle(...$args): void
    {
        $th = $args[0];
        if ($th instanceof \Throwable) {
            // Error message to console
            error_log("Error captured, code: " . $th->getCode() . ', message: ' . $th->getMessage() . PHP_EOL .
                '== Trace Begin == ' . PHP_EOL . PHP_EOL . $th->getTraceAsString() . PHP_EOL . PHP_EOL . '== Trace End ==');
        }
    }
}
