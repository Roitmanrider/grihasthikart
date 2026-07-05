# File Manager Upload Guide

Use this when deployment is done through Hostinger File Manager rather than SSH.

## Recommended Layout

Best structure:

```text
home/
  domains/
    your-domain/
      grihasthikart/
        app/
        bootstrap/
        config/
        database/
        resources/
        routes/
        storage/
        vendor/
      public_html/
        index.php
        .htaccess
        build/
        storage/
```

`public_html/index.php` must require the project paths:

```php
require __DIR__.'/../grihasthikart/vendor/autoload.php';
$app = require_once __DIR__.'/../grihasthikart/bootstrap/app.php';
```

Adjust `../grihasthikart` to the real folder name used on Hostinger.

## If Only public_html Is Available

This is riskier because Laravel private files may be easier to expose accidentally. Prefer asking Hostinger support to set the document root to the Laravel `public` folder.

If unavoidable:

- Do not place `.env` in a publicly browsable location.
- Block direct browser access to private folders.
- Keep backups outside `public_html`.
- Test that `https://domain/.env`, `/vendor`, `/storage/logs`, `/app`, `/database`, and `/routes` are not accessible.

## Upload Steps

1. Build locally with `npm run build`.
2. Create the release ZIP excluding `.env`, `.git`, `node_modules`, local logs, local cache files, and test artifacts.
3. Upload the project ZIP.
4. Extract the application folder outside `public_html` if possible.
5. Copy Laravel `public` contents into `public_html`.
6. Edit `public_html/index.php` paths.
7. Create `.env` manually on the server.
8. Import database SQL using phpMyAdmin if SSH migration is unavailable.
9. Create `public_html/storage` link to `storage/app/public` if possible.
10. Verify images and built assets load in the browser.

## Database Without SSH

- Export SQL from local/staging only after the schema is current.
- Import SQL in Hostinger phpMyAdmin.
- Do not import test/demo users or orders into real production unless approved.
- Keep an untouched backup before replacing any existing production data.

## Storage Without SSH

If `php artisan storage:link` cannot run:

- Try Hostinger File Manager symlink support.
- If symlink is not available, copy `storage/app/public` into `public_html/storage`.
- Document the copy date and repeat the sync after any upload-heavy admin work until a symlink is available.
