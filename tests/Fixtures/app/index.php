<?php

declare(strict_types=1);

use Symfony\Component\HttpFoundation\Request;

require __DIR__.'/bootstrap.php';
$env = $_SERVER['APP_ENV'] ?? 'test';
$kernel = new AppKernel($env, $_SERVER['APP_DEBUG'] ?? ('prod' !== $env));
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);

