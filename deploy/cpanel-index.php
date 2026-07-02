<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$basePath = '/home/grupo17sc/proyecto2_app';
$logFile = $basePath.'/storage/logs/cpanel-bootstrap.log';

try {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', $logFile);

    error_reporting(E_ALL);

    if (file_exists($maintenance = $basePath.'/storage/framework/maintenance.php')) {
        require $maintenance;
    }

    require $basePath.'/vendor/autoload.php';

    $app = require_once $basePath.'/bootstrap/app.php';

    /*
    |--------------------------------------------------------------------------
    | Ajuste para subcarpeta cPanel
    |--------------------------------------------------------------------------
    */
    $app->bind('path.public', function () {
        return '/home/grupo17sc/proyecto2';
    });

    $kernel = $app->make(Kernel::class);

    $response = $kernel->handle(
        $request = Request::capture()
    )->send();

    $kernel->terminate($request, $response);
} catch (Throwable $e) {
    $message = '['.date('Y-m-d H:i:s').'] '
        .$e::class.': '.$e->getMessage()
        .' in '.$e->getFile().':'.$e->getLine()
        .PHP_EOL.$e->getTraceAsString()
        .PHP_EOL.PHP_EOL;

    file_put_contents($logFile, $message, FILE_APPEND);

    http_response_code(500);

    echo 'Error interno. Revisar storage/logs/cpanel-bootstrap.log';
}
