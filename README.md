# BIN MALIK FITNESS — Platform Repository

**Brand:** BIN MALIK FITNESS  
**Domain:** `https://binmalikfitness.com`  
**Primary Market:** Pakistan (`PKR`)  
**Architecture:** Laravel 13 Modular Monolith + MySQL/MariaDB + Blade + Tailwind CSS v4 + Alpine.js

---

## 1. Business Lines

1. **Online Fitness Coaching & Personal Training** *(Primary homepage conversion objective)*
2. **Ecommerce for Supplements, Gym Nutrition & Fitness Accessories** *(Second priority)*
3. **Digital Diet Plans & Workout Programs**
4. **Physical Gym Services & Memberships**

---

## 2. Local Installation & Development

1. Copy `.env.example` to `.env` and configure the MySQL database (`binmalikfitness`) and `binmalikfitness_test`.
2. Install PHP & frontend dependencies:
   ```bash
   composer install
   npm install
   ```
3. Generate application key, run migrations, and seed roles + demonstration data (non-production only):
   ```bash
   php artisan key:generate
   php artisan migrate --seed
   ```
4. Build production frontend assets:
   ```bash
   npm run build
   ```
5. Serve locally (note: `.htaccess` blocks direct Apache traversal of the project root under `htdocs`; serve `public/` directly):
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```

---

## 3. Roles & Permissions

Defined in [`App\Domain\Identity\RolePermissionMatrix`](app/Domain/Identity/RolePermissionMatrix.php) and enforced via `spatie/laravel-permission` + Laravel Policies:

- **`admin` (Owner / Administrator):** Full platform administration (39 permissions), staff/role management, pricing, financial reconciliation, settings, and audit logs.
- **`manager` (Operations Staff):** Day-to-day order processing, fulfillment, authorized inventory actions, assigned coaching/diet workflows, gym check-ins, and support. Cannot change owner privileges, access payment secrets, modify unrestricted settings, view unassigned health data, or issue unrestricted refunds.
- **`coach` (Internal Capability Role):** Assigned to staff members handling client training/nutrition delivery without granting full Manager powers. Scoped by `coach_assignments`.
- **`customer` (Member / Buyer):** Self-service portal for orders, coaching check-ins, entitled diet/workout plans, entitled videos, and gym membership tracking.

---

## 4. Testing

Automated unit and feature tests run against MySQL (`binmalikfitness_test`):

```bash
php artisan test
```
