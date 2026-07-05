# Release Package Guide

This guide prepares a clean GrihasthiKart release package for Hostinger Premium Web Hosting. It does not deploy the project and must not include real secrets.

## Local Release Preparation

Run these commands before creating a release ZIP:

```bash
git status
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan test
php vendor/bin/pint --test
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

Use the full test suite before a real go-live or when migrations, checkout, order, inventory, payment, or security behavior changed. Selected test filters are acceptable only for documentation-only releases or quick verification after a small isolated change.

## Release ZIP Contents

Include:

- `app/`
- `bootstrap/`
- `config/`
- `database/`
- `public/`
- `resources/`
- `routes/`
- `storage/app/public/` when existing media must be shipped
- `vendor/` when Hostinger cannot run Composer
- `composer.json`
- `composer.lock`
- `artisan`
- `.env.production.example`
- `docs/08_Deployment/` when the deployment checklist should travel with the release

Exclude:

- `.git/`
- `node_modules/`
- `tests/` unless production-side test execution is intentionally required
- `.env`
- `.env.backup`
- `.env.production`
- `storage/logs/*`
- `storage/framework/cache/*`
- `storage/framework/sessions/*`
- `storage/framework/views/*`
- local screenshots, temp files, exports, and any personal secrets

## Public Folder And public_html Mapping

Preferred Hostinger structure:

- Put the Laravel project outside `public_html`.
- Copy the contents of Laravel `public/` into `public_html`.
- Adjust `public_html/index.php` so it loads the project `vendor/autoload.php` and `bootstrap/app.php`.

Example:

```php
require __DIR__.'/../grihasthikart/vendor/autoload.php';
$app = require_once __DIR__.'/../grihasthikart/bootstrap/app.php';
```

Fallback:

- If the full project must be placed under `public_html`, document the risk.
- Keep the web root pointed at Laravel `public/` whenever Hostinger allows it.
- Protect sensitive folders and verify `.env`, `app`, `config`, `database`, `routes`, `storage/logs`, `vendor`, and `.git` are not browser-accessible.

## Manual File Manager Workflow

1. Back up current website files from Hostinger.
2. Upload the release ZIP.
3. Extract it into the chosen project folder.
4. Move or copy `public/` contents to `public_html` if needed.
5. Create `.env` manually from `.env.production.example`.
6. Set `APP_KEY`; generate it only if missing.
7. Configure Hostinger MySQL credentials.
8. Import the database through phpMyAdmin or run migrations if SSH is available.
9. Configure the `public/storage` symlink or File Manager equivalent.
10. Clear and rebuild caches.
11. Test the site using the final post-live checklist.

## SSH Workflow If Available

Run from the uploaded Laravel project directory:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Run `php artisan db:seed --force` only when production master data should be inserted or refreshed. Avoid demo/test data on the real store unless explicitly approved.

## Storage And Media

- Product, category, brand, payment proof, and QR images use `storage/app/public`.
- Public browser access depends on `public/storage`.
- Prefer `php artisan storage:link`.
- If symlink creation is unavailable, copy `storage/app/public` to `public/storage` and repeat the sync after new admin uploads until a symlink is available.
- Keep `storage` and `bootstrap/cache` writable by PHP.

## PowerShell Release Script

The optional Windows script creates a package under `releases/`:

```powershell
.\scripts\create-release-package.ps1
.\scripts\create-release-package.ps1 -IncludeVendor
.\scripts\create-release-package.ps1 -IncludeDocs
```

Use `-IncludeVendor` only when Hostinger cannot run Composer. Use `-IncludeDocs` when deployment notes should be included inside the ZIP.
