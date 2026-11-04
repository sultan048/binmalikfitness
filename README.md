# BIN MALIK FITNESS — Platform Repository

**Brand:** BIN MALIK FITNESS
**Domain:** `https://binmalikfitness.com`
**Primary market:** Pakistan (`PKR`)
**Architecture:** Laravel 13 modular monolith + MySQL/MariaDB + Blade + Tailwind CSS v4 + Alpine.js
**Requires:** PHP **8.4.1+** (locked dependencies), Node 20+ for building assets

---

## 1. Business lines

1. **Online fitness coaching & personal training** (primary homepage conversion objective)
2. **E-commerce for supplements, gym nutrition & fitness accessories**
3. **Digital diet plans & workout programs**
4. **Physical gym services & memberships**

---

## 2. Local development (Windows + XAMPP)

XAMPP ships PHP 8.2, which is too old. Install PHP 8.4 next to it (for example in `C:\xampp\php84`) and use that binary for everything below.

1. Copy `.env.example` to `.env`, then switch it to local values: `APP_ENV=local`, `APP_DEBUG=true`, `APP_URL=http://127.0.0.1:8000`, `DEMO_MODE=true`, `SESSION_SECURE_COOKIE=false`, and your local MySQL credentials (databases `binmalikfitness` and `binmalikfitness_test`).
2. Install dependencies:
   ```bash
   composer install
   npm install
   ```
3. Generate the key, migrate, and seed roles + demonstration data (demo data is only seeded when `DEMO_MODE=true` and never in production):
   ```bash
   php artisan key:generate
   php artisan migrate --seed
   ```
4. Build the front-end assets:
   ```bash
   npm run build
   ```
5. Serve `public/` only. The `.htaccess` in the project root deliberately blocks Apache from serving the project root under `htdocs`.
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```
   To serve it through XAMPP's Apache instead, see `deploy/xampp/httpd-binmalikfitness.conf`.

With `DEMO_MODE=true` a role-switcher bar (admin / manager / coach / customer demo accounts) appears at the top of every page.

---

## 3. Production (cPanel)

Follow [`docs/DEPLOYMENT-CPANEL.md`](docs/DEPLOYMENT-CPANEL.md). In short: PHP 8.4, document root = `public/`, production `.env` from `.env.example`, `php artisan migrate --force && php artisan optimize`, and one cron entry for `php artisan schedule:run`.

---

## 4. Roles & permissions

Defined in [`App\Domain\Identity\RolePermissionMatrix`](app/Domain/Identity/RolePermissionMatrix.php) and enforced via `spatie/laravel-permission` + Laravel policies:

- **`admin` (owner / administrator):** full platform administration, staff/role management, pricing, financial reconciliation, settings, audit logs, approval of coach assignments.
- **`manager` (operations staff):** day-to-day order processing, fulfilment, authorised inventory actions, assigned coaching/diet workflows, gym check-ins and support. Cannot change owner privileges, access payment secrets, change unrestricted settings, view unassigned health data, or issue refunds. Coach assignments a manager creates wait for admin approval.
- **`coach` (internal capability role):** delivers training/nutrition to assigned clients only (scoped by approved `coach_assignments`).
- **`customer` (member / buyer):** self-service portal for orders, coaching check-ins, entitled diet/workout plans, entitled videos and gym membership.

Staff accounts must enable two-factor authentication when `REQUIRE_STAFF_2FA=true` (default in production). Customers must verify their e-mail address before using the account area.

---

## 5. Testing

Automated tests run against MySQL (`binmalikfitness_test`):

```bash
php artisan test
vendor/bin/pint --test
```

The same checks run on GitHub Actions for every push and pull request (`.github/workflows/ci.yml`).
