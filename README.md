# Trooms ERP

Horticulture management system — from nursery to dispatch, one farm system.

Trooms ERP tracks a horticulture operation end-to-end: crop planning and budgets, nursery batches, daily field operations (irrigation, fertigation, spraying), labour and attendance, harvest and packhouse traceability, sales and dispatch, inventory and procurement, and a native finance ledger — plus an executive dashboard with AI-generated reports and KPI narratives. It also includes a separate Stables module (horses, guides, rides) for operations that run agritourism alongside farming.

## Tech Stack

- **Backend**: Laravel 13 (PHP 8.3+)
- **Frontend**: Blade views, Tailwind CSS 4, Vite — no SPA framework
- **Database**: SQLite by default (see `.env.example`); swap `DB_CONNECTION` for MySQL/Postgres in production
- **Queue**: database driver — used for AI report generation and KPI narrative jobs
- **AI**: Anthropic Claude, called directly via `App\Services\Ai\AiClient` (no SDK dependency). Optional — the app runs fine without an `ANTHROPIC_API_KEY`, AI features just stay disabled

## Access Control

- **Roles**: `owner`, `md`, `horticulture_manager`, `agronomist`, `farm_supervisor`, `finance_officer`, `sales_officer`, `storekeeper`, `quality_officer`, `packhouse_supervisor`, `driver`, `stable_manager` (`app/Models/User.php`)
- **Module gating**: each module (master data, crop cycles, nursery, finance, AI, admin, etc.) is access-controlled per role via `App\Support\ModuleAccess` middleware, not just per-route policies
- **Invite-only registration**: nobody can self-register unless an owner/admin first approves their email under Users. Once approved, they visit `/register` and set their own password
- **Activity log**: every significant action is recorded to an audit trail, visible to owner/horticulture_manager under Activity Logs

## Getting Started

### Local development

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
composer dev   # runs the PHP server, queue listener, and Vite dev server together
```
