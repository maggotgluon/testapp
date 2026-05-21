<?php

/**
 * Temporary deployment diagnostic for shared hosting.
 *
 * Upload this file into your web document root only while debugging, open it in
 * the browser, then delete it. It intentionally avoids printing secrets.
 */

header('Content-Type: text/plain; charset=utf-8');

$root = dirname(__DIR__);
$checks = [];

$checks['PHP version'] = PHP_VERSION.' '.(version_compare(PHP_VERSION, '8.3.0', '>=') ? 'OK' : 'FAIL: Laravel 13 needs PHP 8.3+');
$checks['Project root'] = is_file($root.'/artisan') ? 'OK: '.$root : 'FAIL: artisan not found beside deploy directory';
$checks['vendor/autoload.php'] = is_file($root.'/vendor/autoload.php') ? 'OK' : 'FAIL: run composer install --no-dev --optimize-autoloader';
$checks['.env'] = is_file($root.'/.env') ? 'OK' : 'FAIL: create production .env';
$checks['APP_KEY'] = getenv('APP_KEY') ?: (is_file($root.'/.env') && str_contains(file_get_contents($root.'/.env'), 'APP_KEY=base64:') ? 'OK' : 'FAIL: run php artisan key:generate');
$checks['storage writable'] = is_writable($root.'/storage') ? 'OK' : 'FAIL: chmod/chown storage writable';
$checks['storage/logs writable'] = is_writable($root.'/storage/logs') ? 'OK' : 'FAIL: chmod/chown storage/logs writable';
$checks['bootstrap/cache writable'] = is_writable($root.'/bootstrap/cache') ? 'OK' : 'FAIL: chmod/chown bootstrap/cache writable';
$checks['public/build'] = is_dir($root.'/public/build') ? 'OK' : 'FAIL: run npm run build locally and upload public/build';
$checks['Document root'] = str_ends_with(realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '', '/public') ? 'OK: points to public' : 'WARN: document root may not point to Laravel public/';

foreach (['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'fileinfo'] as $extension) {
    $checks['ext-'.$extension] = extension_loaded($extension) ? 'OK' : 'FAIL: enable PHP extension';
}

foreach ($checks as $name => $result) {
    echo str_pad($name, 28).$result.PHP_EOL;
}

echo PHP_EOL.'Laravel checks'.PHP_EOL;
echo str_repeat('-', 42).PHP_EOL;

try {
    require_once $root.'/vendor/autoload.php';
    $app = require_once $root.'/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    echo str_pad('Laravel bootstrap', 28).'OK'.PHP_EOL;
    echo str_pad('APP_ENV', 28).config('app.env').PHP_EOL;
    echo str_pad('APP_DEBUG', 28).(config('app.debug') ? 'true' : 'false').PHP_EOL;
    echo str_pad('APP_URL', 28).config('app.url').PHP_EOL;
    echo str_pad('DB connection', 28).config('database.default').PHP_EOL;

    try {
        DB::connection()->getPdo();
        echo str_pad('Database connection', 28).'OK'.PHP_EOL;

        foreach (['users', 'events', 'ticket_orders', 'tickets', 'sessions'] as $table) {
            echo str_pad('table '.$table, 28).(Schema::hasTable($table) ? 'OK' : 'FAIL: missing').PHP_EOL;
        }
    } catch (Throwable $exception) {
        echo str_pad('Database connection', 28).'FAIL: '.$exception->getMessage().PHP_EOL;
    }
} catch (Throwable $exception) {
    echo str_pad('Laravel bootstrap', 28).'FAIL: '.$exception->getMessage().PHP_EOL;
}

echo PHP_EOL.'Delete this file after debugging.'.PHP_EOL;
