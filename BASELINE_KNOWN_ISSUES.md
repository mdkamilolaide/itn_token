# Baseline known issues — out of scope for the Vue 3 / Tailwind refactor

Captured at git tag `baseline-pre-vue3` (commit `393c1ab`).

These problems exist in the cloned source (psmitn@dev, HEAD `df9ba7e`) **before** any refactor work begins. They are explicitly out of scope for the refactor — its job is to preserve behavior, not to fix unrelated pre-existing bugs.

If a problem appears after a refactor commit and is also listed here, it is NOT a regression.

---

## Test counts (corrects an earlier note)

The PHPUnit suite contains **1,277 tests**, not 156 as the original plan draft stated.

```
Feature:     120 tests
Integration: 1003 tests
Unit:        154 tests
Total:       1277 tests
```

`composer.json` requires `phpunit/phpunit: 12` and `phpstan/phpstan: 2.1`.

Effort estimates in the plan that referenced "156 tests" are unaffected — the refactor doesn't touch backend tests.

---

## PHPStan: 97 errors at baseline

Run: `composer phpstan`

These errors live in pre-existing PHP code. None are in files the refactor will modify (the refactor only adds `src/`, `lib/vite_loader.php`, and edits `pages/js.php`, `pages/css.php`, `lib/data/system_structure.json`). Sample issues:

- `controller/users/userManage.cont.php:61` — `str_pad` called with int instead of string.
- `mysql.min.php:145` — `MysqlCentry::Execute()` called with 2 arguments when method requires 1.

All 97 errors are tracked in upstream `psmitn` already.

Action: do NOT fix these as part of this refactor. They get a separate ticket.

---

## PHPUnit: at least 1 pre-existing failure

Run: `vendor/bin/phpunit --testsuite=Unit --stop-on-failure`

```
1) Tests\Unit\Controllers\Netcard\EtokenControllerTest::testChangeLengthSupportsZero
Failed asserting that null is identical to Array &0 [].

  C:\laragon\www\itn_token\tests\Unit\Controllers\Netcard\EtokenControllerTest.php:37

Triggered by warning:
  C:\laragon\www\itn_token\lib\controller\netcard\etoken.cont.php:108
  Undefined variable $etoken_data
```

Discovered with `--stop-on-failure`. The full suite has not yet been run end-to-end — there may be other pre-existing failures. The user should run `vendor/bin/phpunit` to completion and append any additional baseline failures to this file before Phase 1's loader changes land.

---

## Phase 0.5 manual checks still owed

The following items in the plan's Phase 0.5 require browser interaction and have NOT yet been verified at this baseline. The user must perform these and either confirm they pass or document failures here:

- [ ] Login with a known test user — JWT cookie set, redirects to dashboard
- [ ] All 14 modules in the sidebar load without console errors (admin, users, beneficiary, smc, distribution, logistics, mobilization, netcard, activity, sample, device, monitoring, reporting, eolin)
- [ ] `services.data.php?qid=<a known qid>`, `services.table.php`, `services.export.php` return expected JSON shape

If any of the above fails at baseline, the failure is pre-existing and goes here, not on the refactor's account.

---

## Backup pointers (so the diverged work is not lost)

The previous `itn_token` (with the in-progress backend `services/` split-up and ~52 modified Vue 2 files) is preserved at:

- Tarball: `C:\laragon\www\itn_token_backup_pre_refactor_2026-05-07.tar.gz` (83 MB)
- CLAUDE.md snapshot: `C:\laragon\www\itn_token_CLAUDE_md_snapshot.md` (already restored into this repo)
- services/ dir snapshot: `C:\laragon\www\itn_token_services_dir_snapshot\`

Port from these deliberately as each module migrates in Phase 4.
