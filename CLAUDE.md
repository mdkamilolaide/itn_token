# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Ipolongo Platform** — A custom PHP MVC web application for managing large-scale public health campaigns in Nigeria:
- Insecticide-Treated Net (ITN) distribution
- Seasonal Malaria Chemoprevention (SMC) drug administration

Developed by GHSC-PSM SID team, sponsored by PMI. Current version: 5.0.49.

## Commands

```bash
# Install PHP dependencies
composer install

# Run all tests
vendor/bin/phpunit

# Run a specific test file
vendor/bin/phpunit tests/Feature/DataExport/DataExportWorkflowTest.php

# Run a single test method
vendor/bin/phpunit --filter testCSVExportWorkflow tests/Feature/DataExport/DataExportWorkflowTest.php

# Static analysis
vendor/bin/phpstan
# or
composer phpstan
```

Tests require a running MySQL database. All test users use password `TestPass123`.

## Architecture

### Entry Points

| File | Purpose |
|------|---------|
| `index.php` | Main app: validates JWT cookie, loads session globals, renders layout |
| `login.php` | Authentication + JWT token generation |
| `api.php` | REST API v2 with JWT middleware and privilege checking |
| `api_v1.php` | Legacy API v1 (backward compatibility) |
| `pages/page.php` | Module router — large switch statement dispatching to view templates |

### Request Flow

1. Browser hits `index.php` → JWT cookie `Aerwuhnfewujf3olsuhrnbc3oiwuhwG` validated using `lib/privateKey.pem` (HS512)
2. User data loaded into session globals (`$v_g_id`, `$v_g_rolename`, `$priv`, etc.)
3. Module/submodule from query string routed through `pages/page.php` switch to the appropriate template under `pages/`
4. Templates call controller classes for data; API calls go through `api.php` with privilege checks

### Controller Autoloading

`lib/autoload.php` registers `myAutoLoader()` — a PSR-0 style loader mapping namespaces to `lib/controller/` subdirectories. Controllers are organized by domain namespace:

```
lib/controller/
├── dashboard/     # Dashboard aggregators
├── distribution/  # ITN distribution logic
├── form/          # ININ-A/B/C and SMC survey forms
├── mobilization/  # Field mobilization
├── monitor/       # Monitoring
├── netcard/       # Net card transactions
├── reporting/     # Report aggregation + Excel exports
├── smc/           # SMC logistics, drug admin, inventory, periods
├── system/        # FCM, device registry, activity logs
├── training/      # Training activities
└── users/         # User CRUD, roles, privileges, batch ops
```

### Module System

`lib/data/system_structure.json` defines all modules, their navigation labels, and per-module CSS/JS assets to lazy-load. When adding a new module, register it here. Page routing (`pages/page.php`) and the nav bar (`pages/nav.php`) both read from this config.

### Database Layer

`lib/mysql.min.php` — custom `MysqlPdo` wrapper around PDO. Always use parameterized queries via this class (`DataTable`, `Execute`, `Insert`, etc.). Raw `GetMysqlDatabase()` returns the PDO connection.

Geographic hierarchy: **State → LGA → Cluster → Ward → Distribution Point (DP)**. Most data queries are scoped by this hierarchy.

### Authentication & Authorization

- JWT generated on login and stored in a cookie; token payload contains `user_id`, `role`, `geo_level`, `system_privilege`, and per-module privilege list
- `IsPrivilegeInArray()` and `IsPlatformInArray()` are the authorization guards — call these before any sensitive operation
- Privilege model is granular: each user has a JSON array of module-level access rights

### Frontend — Vue 2 Modules

All interactive UI is written in **Vue 2** and lives under `app-assets/app/`. There is **no build step** — files are plain JS served directly to the browser. Vue is loaded from `app-assets/vendors/third-parties/vue/vue.js`.

**Component pattern:** inline components registered via `Vue.component()` with backtick-string templates. No `.vue` single-file components.

```
app-assets/app/
├── common.js           # Global utilities: alert.*, overlay.*, common.*, EventBus
├── admin/              # Admin log & provision
├── users/              # User dashboard, list, group, log
├── distribution/       # Dashboard, DP list, list, unredeemed nets, reporting
├── mobilization/       # Dashboard, list, map, microlist, reporting
├── netcard/            # Allocation, dashboard, movement, push, unlock
├── smc/logistics/      # Allocation, shipment, movement, stock batch, etc.
├── device/             # Device allocation, login log, registry
├── activity/           # Dashboard, list, reporting
├── monitoring/         # Home
├── reporting/          # Home
└── eolin/              # Dashboard
```

**Module loading:** `pages/js.php` reads `?module=` and `?submodule=` from the query string, looks up the corresponding JS files in `lib/data/system_structure.json`, and emits `<script>` tags. To add a new module's JS, register it in `system_structure.json`.

**Inter-component communication:** a global `EventBus = new Vue()` defined in each module file. Common events: `g-event-goto-page`, `g-event-refresh-page`, `g-event-reset-form`. Newer files (e.g. `smc/logistics/`) use `Vue.observable()` for shared state and `Vue.mixin()` for shared methods.

**Backend API:** three PHP service endpoints consumed via Axios:
- `services.data.php` — general data reads/writes, addressed by `?qid=NNN`
- `services.table.php` — paginated DataTable responses
- `services.export.php` — Excel/CSV export triggers

Responses use `{ result_code: "200", data: [...], recordsTotal: N }`. Authentication is automatic via the JWT cookie.

**Shared utilities from `common.js`:**
- `alert.Success/Error/Info/Warning/Delete(...)` — Toastr-based notifications
- `overlay.show() / overlay.hide()` — jQuery BlockUI loading spinner
- `common.GoToPage/GoToUrl/GoBack()` — navigation helpers
- `common.DataService / TableService / 
ExportService` — service URL constants

Other vendored libraries: jQuery, DataTables, Bootstrap 4, ApexCharts, Select2, Flatpickr, Toastr, jsXlsx (Excel export), jsTree; mPDF is server-side only (PDF + QR code generation).

### Configuration

`lib/config.php` — no `.env` file. Key settings include HTTPS enforcement (`$config_ht_protocol_secure`), JWT config, timezone (`Africa/Lagos`), and date format (DD/MM/YYYY). Input is sanitized via `CleanData()` in `lib/common.php`.

### Testing Notes

- `tests/bootstrap.php` seeds minimal fixtures and configures the test DB connection
- Transaction rollback is opt-in per test class (`$useTransactionRollback = true`) — disabled by default because controllers open their own DB connections
- SQL migration/backup snapshots are in `db/` (timestamped)
