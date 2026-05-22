<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$configuredKey = (string) env('DEPLOY_MIGRATE_KEY', '');
$providedKey = (string) ($_GET['key'] ?? '');

if ($configuredKey === '' || ! hash_equals($configuredKey, $providedKey)) {
    http_response_code(404);
    exit('404 Not Found');
}

header('Content-Type: text/plain; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

echo "Laravel deploy migration\n";
echo "========================\n\n";
echo 'Environment: '.app()->environment()."\n";
echo 'Database: '.config('database.default')."\n\n";

try {
    Artisan::call('migrate', ['--force' => true]);
    echo Artisan::output()."\n";

    if (($_GET['seed'] ?? '') === '1') {
        Artisan::call('db:seed', ['--force' => true]);
        echo Artisan::output()."\n";
    }

    Artisan::call('optimize:clear');
    echo Artisan::output()."\n";

    echo "Done.\n";
    echo "IMPORTANT: delete public/deploy-migrate.php after migration.\n";
} catch (Throwable $exception) {
    http_response_code(500);
    echo "FAILED\n";
    echo $exception->getMessage()."\n";
}
