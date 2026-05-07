TESTS.md

This document explains the structure of the tests in the `tests/` directory, how to run them, and items to watch for.

NOTE: This document is explanatory only; it does not modify repository files or committed scripts.

Summary
- Test framework: PHPUnit
- Location: `tests/`
- Bootstrap: `tests/bootstrap.php` — sets up autoloading, minimal fixture seeding, and test environment configuration
- Environment: Run from CLI (PowerShell recommended on Windows). Tests require a local database connection provided by `lib/mysql.min.php` (GetMysqlDatabase()).

Test layout
- tests/Unit: Unit tests (small units, controller logic, libraries)
- tests/Feature: Feature/integration tests (controllers, endpoints, workflows)
- tests/Integration: Larger integration tests
- tests/Helpers: Test helpers (assertion traits, factories)
- tests/Fixtures: Test data files and fixture loaders
- tests/TestCase.php: Common base test class shared by all tests
- tests/bootstrap.php: PHPUnit bootstrap file; runs before tests to configure autoload and seed minimal fixtures when running via CLI

How to run tests (Windows PowerShell)
1) Install dependencies (project root):

```powershell
php composer.phar install
# or if composer is available globally:
# composer install
```

2) Run the full test suite (from project root):

```powershell
# If vendor\bin\phpunit is available
vendor\bin\phpunit
# Alternative (direct PHP path to phpunit):
php vendor\phpunit\phpunit\phpunit
```

3) Run a single test class file:

```powershell
vendor\bin\phpunit tests\Feature\DataExport\DataExportWorkflowTest.php
```

4) Run a single test method:

```powershell
vendor\bin\phpunit --filter testCSVExportWorkflow tests\Feature\DataExport\DataExportWorkflowTest.php
```

Important notes and findings
- Bootstrap and database:
  - `tests/bootstrap.php` seeds minimal fixtures into the test database. Tests therefore expect a database to be available. For CI, provide a test database or refactor tests to be isolated.
  - Transaction rollback behaviour: `tests/TestCase.php` has transaction rollback disabled by default because application controllers open their own DB connections. Tests that perform both write and read through `$this->db` can enable rollback by setting `$useTransactionRollback = true` in the test class.
  - All user passwords changed to `TestPass123` in order to make testing easy on database, if further want to use this test, you should complete creating a database for testing and use test users.
