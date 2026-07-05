# Deployment Checklist

## Before Packaging

- Confirm working tree is clean.
- Confirm all MVP tests pass.
- Confirm `php vendor/bin/pint --test` passes.
- Confirm `npm run build` passes.
- Confirm `public/build` exists.
- Confirm no real `.env` file will be included.
- Confirm uploaded media has been copied from `storage/app/public` if needed.
- Confirm database backup exists.

## Release ZIP Contents

Include:

- `app`
- `bootstrap`
- `config`
- `database`
- `public`
- `resources`
- `routes`
- `storage` without local logs/cache noise
- `vendor` if Composer cannot run on the server
- `composer.json`
- `composer.lock`
- `.env.production.example`
- built assets in `public/build`

Exclude:

- `.env`
- `.env.backup`
- `.env.production`
- `.git`
- `.codex`
- `.cursor`
- `.idea`
- `.vscode`
- `node_modules`
- `tests` unless production test execution is required
- local log files in `storage/logs`
- local cache/session/view files in `storage/framework`
- screenshots, temp files, and local exports

## Manual Upload Flow

1. Put the site in maintenance mode if replacing a live site.
2. Download a backup of current Hostinger files.
3. Download or export a database backup.
4. Upload the release ZIP.
5. Extract the Laravel project outside `public_html` when possible.
6. Put only Laravel public assets/front controller in `public_html`.
7. Create the production `.env` manually from `.env.production.example`.
8. Import or migrate the database.
9. Create the public storage link or File Manager equivalent.
10. Clear and rebuild Laravel caches when SSH is available.
11. Run the post-deployment tests.

## SSH Command Flow

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Run `php artisan db:seed --force` only when approved master seeders are intended for production.
