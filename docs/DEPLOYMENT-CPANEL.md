# Deploying BIN MALIK FITNESS to cPanel

Checklist for a cPanel host. Work through it top to bottom on first deploy; the "Every deploy" section applies to each update.

## 1. Before you start

- [ ] **PHP 8.3 or newer is available.** In cPanel open *MultiPHP Manager* and pick 8.3 (or 8.4) for the domain. `composer.lock` is resolved for PHP 8.3 (`config.platform.php`), so 8.3 and 8.4 both work; 8.2 or older will not start.
- [ ] PHP extensions enabled (cPanel → *Select PHP Version* → Extensions): `bcmath ctype curl fileinfo gd intl mbstring openssl pdo_mysql tokenizer xml zip sodium`.
- [ ] SSH or Terminal access (cPanel → *Terminal*). Composer is usually available as `composer`; if not, use `php composer.phar`.
- [ ] A MySQL database and user (cPanel → *MySQL Databases*) with ALL PRIVILEGES on the database.
- [ ] The mailbox `no-reply@binmalikfitness.com` exists (cPanel → *Email Accounts*). Used for password-reset, verification, order and alert e-mails.

## 2. Folder layout

Keep the application **outside** `public_html` so `.env`, `vendor/` and `storage/` are never web-reachable:

```
/home/<user>/binmalikfitness/        <- the repository
/home/<user>/binmalikfitness/public  <- the ONLY web-served folder
```

Point the domain at `public/`:

- cPanel → *Domains* → `binmalikfitness.com` → *Manage* → set **Document Root** to `binmalikfitness/public`.
- If the host does not allow changing the document root of the primary domain, make `public_html` a symlink to `binmalikfitness/public` (ask hosting support if unsure). Do **not** copy the whole project into `public_html`.

## 3. First deploy

```bash
cd ~
git clone https://github.com/sultan048/binmalikfitness.git
cd binmalikfitness

composer install --no-dev --optimize-autoloader
cp .env.example .env          # production defaults
php artisan key:generate
```

Edit `.env` (cPanel → *File Manager* or `nano .env`):

| Key | Value |
| --- | --- |
| `APP_URL` | `https://binmalikfitness.com` |
| `APP_ENV` / `APP_DEBUG` | `production` / `false` |
| `DEMO_MODE` | `false` |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | from step 1 (cPanel prefixes them, e.g. `cpuser_bmf`) |
| `MAIL_PASSWORD` | password of `no-reply@binmalikfitness.com` (wrap in double quotes) |
| `ERROR_ALERT_EMAIL` | where error alerts should go |
| `MYSQLDUMP_PATH` | usually `mysqldump` (or `/usr/bin/mysqldump`) |
| `TRUSTED_PROXIES` | `*` if the site is behind Cloudflare, otherwise empty |

Then:

```bash
php artisan migrate --force
php artisan db:seed --class=RoleAndPermissionSeeder --force   # roles & permissions only, never demo data
php artisan optimize                                          # cache config, routes, views, events
```

Front-end assets are **not** committed. Build them on your computer and upload:

```bash
npm ci && npm run build        # on your computer
# upload the generated public/build/ folder to ~/binmalikfitness/public/build/
```

Create the first admin account (Terminal):

```bash
php artisan tinker
>>> $u = App\Models\User::create(['name' => 'Owner', 'email' => 'you@example.com', 'phone' => '03000000000', 'password' => 'a-long-unique-password', 'status' => 'active', 'email_verified_at' => now()]);
>>> $u->assignRole('admin');
```

Sign in, set up 2FA on the page you are sent to, and save the recovery codes.

## 4. Cron jobs (cPanel → *Cron Jobs*)

One entry, every minute:

```
* * * * * cd /home/<user>/binmalikfitness && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler then runs, on its own timetable:

- `queue:work --stop-when-empty` every minute (queued e-mails such as order notifications)
- `backup:run` daily at 02:30 (database dump + private uploads, kept `BACKUP_KEEP_DAYS` days, in `storage/app/backups`)
- `security:prune` daily (expired idempotency keys, password-reset tokens, old audit logs)

The `php` on the cron path may be older than 8.3 (shared hosts often default to 8.2). Use the version shortcut or full path instead, e.g. `ea-php83` or `/opt/cpanel/ea-php83/root/usr/bin/php`. In Terminal run the same way: `ea-php83 artisan ...`, `ea-php83 ~/composer.phar ...`.

Download a backup off the server regularly (cPanel *File Manager* → `storage/app/backups`), or enable the host's own backup service as well.

## 5. Security checks after go-live

- [ ] `https://binmalikfitness.com/.env` returns 404/403.
- [ ] `https://binmalikfitness.com/design-system` returns 404 and there is no "Role Switcher" bar at the top.
- [ ] Force HTTPS (cPanel → *Domains* → *Force HTTPS Redirect*). The app sends HSTS once served over HTTPS.
- [ ] Password reset: request a reset for your admin e-mail and confirm the mail arrives from `no-reply@binmalikfitness.com`.
- [ ] Register a test customer and confirm the verification e-mail arrives.

## 6. Every deploy

```bash
cd ~/binmalikfitness
php artisan down
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear && php artisan optimize
php artisan up
```

Upload a fresh `public/build/` whenever CSS/JS changed.
