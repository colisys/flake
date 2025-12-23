<?php

namespace App\Controller;

use Flake\Attribute\Controller\Controller;
use Flake\Attribute\Controller\RestfulMapping;

use function Flake\View\view;

#[Controller()]
class IndexController
{
    #[RestfulMapping('GET', '/hello')]
    public function index()
    {
        return view('index', ['time' => time()]);
    }
}
