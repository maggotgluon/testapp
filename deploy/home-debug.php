<?php

/**
 * Temporary Laravel request diagnostic for shared hosting.
 *
 * Upload into the Laravel public directory, open /home-debug.php, copy the
 * output, then delete this file. Do not leave it on a production server.
 */

header('Content-Type: text/plain; charset=utf-8');

$root = dirname(__DIR__);

try {
    require_once $root.'/vendor/autoload.php';
    $app = require_once $root.'/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    echo 'Laravel bootstrap: OK'.PHP_EOL;
    echo 'APP_ENV: '.config('app.env').PHP_EOL;
    echo 'APP_DEBUG: '.(config('app.debug') ? 'true' : 'false').PHP_EOL;
    echo 'LOG_CHANNEL: '.config('logging.default').PHP_EOL;
    echo 'storage/logs writable: '.(is_writable($root.'/storage/logs') ? 'OK' : 'FAIL').PHP_EOL;

    $request = Illuminate\Http\Request::create('/', 'GET');
    $response = $app->make(Illuminate\Contracts\Http\Kernel::class)->handle($request);

    echo 'Homepage status: '.$response->getStatusCode().PHP_EOL;
    echo 'Homepage content preview:'.PHP_EOL;
    echo substr(strip_tags((string) $response->getContent()), 0, 1000).PHP_EOL;
} catch (Throwable $exception) {
    echo 'EXCEPTION: '.get_class($exception).PHP_EOL;
    echo 'MESSAGE: '.$exception->getMessage().PHP_EOL;
    echo 'FILE: '.$exception->getFile().':'.$exception->getLine().PHP_EOL;
    echo PHP_EOL.'TRACE:'.PHP_EOL;
    echo $exception->getTraceAsString().PHP_EOL;
}

echo PHP_EOL.'Delete this file after debugging.'.PHP_EOL;
