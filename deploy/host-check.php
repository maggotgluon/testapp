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
$checks['bootstrap/cache writable'] = is_writable($root.'/bootstrap/cache') ? 'OK' : 'FAIL: chmod/chown bootstrap/cache writable';
$checks['public/build'] = is_dir($root.'/public/build') ? 'OK' : 'FAIL: run npm run build locally and upload public/build';

foreach (['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'fileinfo'] as $extension) {
    $checks['ext-'.$extension] = extension_loaded($extension) ? 'OK' : 'FAIL: enable PHP extension';
}

foreach ($checks as $name => $result) {
    echo str_pad($name, 28).$result.PHP_EOL;
}

echo PHP_EOL.'Delete this file after debugging.'.PHP_EOL;
