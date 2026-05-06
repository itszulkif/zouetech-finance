# Zouetech Finance (Core PHP + MySQLi)

## Stack
- Core PHP (no framework)
- MySQLi (prepared statements)
- AJAX (Fetch API)
- JavaScript + HTML
- Tailwind CSS (CDN)

## Setup
1. Create database tables using `database/schema.sql`.
2. Update DB credentials in `config/config.php`.
3. Open `/zou-finance/public/login.php`.
4. Use **Initial setup** form to create the first admin account.
5. Login and use the dashboard.

## Implemented Core Rules
- Distributed income: 50% company, 50% partner pool, pool split by partner percentages.
- Company only + external source income: 100% company.
- Partner percentage validation prevents totals above 100%.
- Distributed income requires partner total = 100%.
- Company expenses reduce company balance.
- Partner expenses create ledger debits and receivable liability tracking.
- Partner ledger computes share, used, remaining, receivable.

## Security
- Admin sessions (`$_SESSION`) with regeneration on login.
- Optional partner ledger sessions for read-only partner access.
- SQL injection prevention using prepared statements.
- Backend input validation and sanitization.
- Error logging to `logs/app.log`.

## API Endpoints
- `POST /api/setup_admin.php`
- `POST /api/login.php`
- `POST /api/logout.php`
- `POST /api/partner_login.php`
- `GET|POST|DELETE /api/partners.php`
- `POST /api/income.php`
- `GET /api/income_preview.php`
- `POST /api/expenses.php`
- `GET /api/dashboard.php`
- `GET /api/ledger.php`
- `GET /api/partner_ledger.php`
- `GET /api/reports.php`
- `GET /api/transactions.php`
