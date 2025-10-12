<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Flake\App;

require __DIR__ . '/../routes/examples.php';
require __DIR__ . '/../middlewares/examples.php';


App::run();
