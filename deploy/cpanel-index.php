<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$basePath = '/home/grupo17sc/proyecto2_app';
$baseUri = '/inf513/grupo17sc/proyecto2';


$_SERVER['SCRIPT_NAME'] = $baseUri.'/index.php';
$_SERVER['PHP_SELF'] = $baseUri.'/index.php';
$_SERVER['SCRIPT_FILENAME'] = __FILE__;

if (isset($_SERVER['REQUEST_URI'])) {
    $requestUri = $_SERVER['REQUEST_URI'];
    $queryString = '';

    if (($queryPosition = strpos($requestUri, '?')) !== false) {
        $queryString = substr($requestUri, $queryPosition);
        $requestUri = substr($requestUri, 0, $queryPosition);
    }

    if ($requestUri === $baseUri || $requestUri === $baseUri.'/') {
        $_SERVER['REQUEST_URI'] = '/'.$queryString;
    } elseif (str_starts_with($requestUri, $baseUri.'/')) {
        $_SERVER['REQUEST_URI'] = substr($requestUri, strlen($baseUri)).$queryString;
    }
}

if (file_exists($maintenance = $basePath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $basePath.'/vendor/autoload.php';

$app = require_once $basePath.'/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
