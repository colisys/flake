<?php

namespace Flake\DI\Contract;

use Psr\Container\ContainerInterface;

interface AfterAutoRegister
{

    public function onAfterAutoRegiste(ContainerInterface $container): void;
}
