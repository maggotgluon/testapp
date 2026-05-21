# Deploying TicketFlow on Passenger / Shared Hosting

Passenger's "Web application could not be started" page is only the outer error. Search the Passenger or web server log for the shown Error ID to see the real PHP exception.

## Most Common Causes

1. The host is not using PHP 8.3 or newer. This app uses Laravel 13 and requires PHP `^8.3`.
2. The domain document root points to the project root instead of `public`.
3. `vendor/` is missing because `composer install` was not run on the server.
4. `.env` is missing, `APP_KEY` is empty, or database credentials are wrong.
5. `storage/` or `bootstrap/cache/` is not writable by the web user.
6. Migrations were not run, so tables like `events`, `sessions`, or `ticket_orders` do not exist.
7. Passenger is configured as a Node/Python/Ruby app instead of serving the PHP/Laravel public entry point.

## Recommended Host Settings

- PHP version: 8.3 or 8.4
- Document root: `/path/to/testapp/public`
- App root, if Passenger asks: `/path/to/testapp`
- Startup file, if Passenger asks for PHP: `public/index.php`
- Environment: `production`

Do not expose the Laravel project root as the web root. Only `public/` should be public.

## Production `.env`

Use values like these on the server:

```dotenv
APP_NAME=TicketFlow
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
```

Then generate the key:

```bash
php artisan key:generate --force
```

## Upload / Install Steps

On the server:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Locally before uploading static assets:

```bash
npm install
npm run build
```

Upload `public/build` with the app.

## Permissions

These must be writable by the web server user:

```bash
chmod -R ug+rw storage bootstrap/cache
```

Some shared hosts need:

```bash
chmod -R 775 storage bootstrap/cache
```

## Restart Passenger

Passenger notices `tmp/restart.txt`. After each deploy:

```bash
mkdir -p tmp
touch tmp/restart.txt
```

## Temporary Host Check

Upload `deploy/host-check.php` temporarily and open it in the browser. It checks PHP version, required extensions, `.env`, `vendor`, writable folders, and built assets.

It also attempts to bootstrap Laravel and connect to the database. If the outer checks pass but the homepage still fails, look closely at:

- `Document root`
- `Laravel bootstrap`
- `Database connection`
- missing table checks

Delete it immediately after debugging.

If Laravel can bootstrap but the homepage still fails and no `storage/logs/laravel.log` file appears, upload `deploy/home-debug.php` into the public directory and open `/home-debug.php`. It catches the homepage exception directly. Delete it immediately after debugging.

## If You Cannot Point the Domain to `public/`

Best fix: change the domain document root to `public`.

If your host only allows `public_html`, copy the contents of Laravel's `public/` folder into `public_html`, then edit `public_html/index.php` so these paths point to the project folder outside `public_html`:

```php
require __DIR__.'/../testapp/vendor/autoload.php';
$app = require_once __DIR__.'/../testapp/bootstrap/app.php';
```

The exact `../testapp` path depends on where you uploaded the project.
