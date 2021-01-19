<?php

/*
 * This file is part of the ESQL project.
 *
 * (c) Antoine Bluchet <soyuka@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use Symfony\Component\HttpFoundation\Request;

require __DIR__.'/bootstrap.php';
$env = $_SERVER['APP_ENV'] ?? 'test';
$kernel = new AppKernel($env, $_SERVER['APP_DEBUG'] ?? ('prod' !== $env));
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
