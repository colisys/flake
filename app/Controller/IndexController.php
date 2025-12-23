<?php

namespace App\Controller;

use App\Model\UserModel;
use Flake\Attribute\Controller\Controller;
use Flake\Attribute\Controller\RestfulMapping;
use Flake\Persistent\Attribute\ModelMapping;

use function Flake\dd;
use function Flake\View\view;

#[Controller()]
class IndexController
{
    #[RestfulMapping('GET', '/hello')]
    public function index(#[ModelMapping(pk: '>id')] UserModel $model)
    {
        dd($model);
        return view('index', ['time' => time()]);
    }
}
