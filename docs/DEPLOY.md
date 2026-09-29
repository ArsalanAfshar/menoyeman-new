# Deployment — MenoyeMan

Two supported targets: **Iranian shared hosting (cPanel)** and **VPS**. No step requires
external CDNs or services blocked in Iran. Queueing uses the `database` driver with Cron
(no Redis, no supervisor needed).

## Common preparation

```bash
git clone <repo> menoyeman-new && cd menoyeman-new
composer install --no-dev --optimize-autoloader
cp .env.example .env && php artisan key:generate
```

Build the frontend **before uploading** (or commit the built assets — `public/build` is tracked):

```bash
npm ci && npm run build
```

`.env` production values:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://menoyeman.ir
APP_LOCALE=fa

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=menoyeman
DB_USERNAME=...
DB_PASSWORD=...

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

SMS_DRIVER=smsir
SMS_IR_API_KEY=...
SMS_IR_VERIFY_TEMPLATE_ID=...

ZARINPAL_MERCHANT_ID=...
ZARINPAL_MODE=sandbox   # switch to live when ready
```

Then:

```bash
php artisan migrate --force
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Cron (shared hosting → cPanel **Cron Jobs**, VPS → `crontab -e`):

```cron
* * * * * cd /path/to/menoyeman-new && php artisan schedule:run >> /dev/null 2>&1
```

## cPanel shared hosting

1. Create the app in a subdirectory (e.g. `~/apps/menoyeman`) **outside** `public_html`, and point
   the domain's document root to `~/apps/menoyeman/public` (cPanel → **Domains** → document root,
   or an addon domain). If the host forces `public_html`, upload the project to `~/apps/menoyeman`
   and symlink `public_html` → `apps/menoyeman/public`.
2. Select **PHP 8.2+** in **MultiPHP Manager** and enable extensions: `pdo_mysql`, `gd`, `mbstring`,
   `openssl`, `fileinfo`, `sodium`, `intl`.
3. Upload the repository (Git via terminal, or File Manager + extract). Run the Common preparation
   steps in the cPanel **Terminal**.
4. MySQL: create database + user in **MySQL® Databases**, grant ALL to the app user, set `.env`.
5. Point `APP_URL` to the real domain and add the cron job above.
6. `.env` must not be web-accessible (it lives outside the document root by construction).

## VPS (Ubuntu 22.04+)

```bash
apt update && apt install -y nginx mysql-server php8.3-fpm php8.3-mysql php8.3-gd \
  php8.3-mbstring php8.3-xml php8.3-curl php8.3-intl php8.3-zip unzip git
```

Nginx site (document root → `/var/www/menoyeman/public`):

```nginx
server {
    listen 443 ssl http2;
    server_name menoyeman.ir;
    root /var/www/menoyeman/public;

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }

    location ~* \.(?:css|js|jpg|png|svg|woff2|webmanifest|ico)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

Then run the Common preparation as the deploy user, set file ownership (`www-data`),
and add the cron job. TLS: `certbot --nginx -d menoyeman.ir` (or a local CA if required).

## Smart polling instead of WebSockets

Order/dashboard live updates poll JSON endpoints every 5–8 s (with jitter). No WebSocket
service, no sticky sessions — compatible with any shared host.

## Fonts & assets

Vazirmatn (variable WOFF2), compiled CSS/JS (`public/build`), brand assets (`public/brand`),
and the favicon/PWA set are all served from the app itself. **Never** reference Google Fonts
or external CDNs.

## Database notes

- Prod uses **MySQL 8** (`utf8mb4`, `utf8mb4_persian_ci` acceptable for text columns).
- Dev uses **SQLite**, chosen automatically by `.env.example`.
- Timestamps are stored in **UTC**; display conversion to Jalali/Tehran happens at render time.
- Backups: Phase 9 ships an admin backup exporter; until then use cPanel backups or
  `mysqldump` in the cron schedule.
