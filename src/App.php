<?php

namespace Flake;

class App
{
    public static function run()
    {
        Events::boot();
        Events::dispatch('App.Start');

        try {
            [$request, $response] = Middleware::run();
            Router::dispatch($request, $response);
        } catch (\Throwable $th) {
            Events::dispatch('App.Error', $th);
            $response?->status(500)->send("Internal Server Error");
            die();
        }
    }
}
