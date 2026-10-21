# Fix: Undefined SITE_ID constant on first deploy + Install flow errors

- **ID/Ticket:** Runtime fatal error - Undefined constant "Vvveb\System\Component\SITE_ID" + Install form submit errors
- **Branch:** `fix/undefined-site-id-first-deploy-v2`, `fix/undefined-site-id-startup-v2`, `vc-dev` (release)
- **Owner hiện tại:** agent
- **Trạng thái tổng:** `MERGED` (commits 50426f4, dace7b0, 7ff49f9, 51c358a, e89d16f, b0305f7, b0a4dca pushed to vc-dev & vc-prod)
- **Tạo ngày:** 2026-10-21 · **Cập nhật:** 2026-10-21

## Objective

Fix 3 related issues on first deploy:
1. Fatal error: `Undefined constant "Vvveb\System\Component\SITE_ID"` in `system/component/component.php:180`
2. Warning: `foreach() argument must be array|object, null given` in `system/sites.php:326`
3. Warning: `file_put_contents(config/db.php): Permission denied` + `Cannot modify header information - headers already sent`

## Root Cause

**Issue 1 - SITE_ID namespace bug:**
`Component::loadComponents()` uses `SITE_ID` constant, but this constant is only defined in `startup.php` inside `FrontController::dispatch()`. The Component class is instantiated during View construction (first request), but `SITE_ID` is defined later.

**Issue 2 - config('sites') returns null:**
`Sites::getSiteData()` calls `config('sites')` before config is loaded during install form submit, returning null → foreach warning → output sent → header redirect fails.

**Issue 3 - config/ directory permissions:**
Docker entrypoint doesn't create `config/` directory with proper permissions for `www-data` user.

**Issue 4 - Auto seed data on container start:**
docker-entrypoint.sh automatically runs CLI install on first boot, seeding default data before user can access web installer.

## Fix Summary

### PR #8 (commit 7ff49f9): SITE_ID namespace fix
- `system/component/component.php`: Define `SITE_ID` in global namespace with leading backslash (`\define('\SITE_ID', 1)`)
- All `SITE_ID` usages now explicitly reference global constant (`\SITE_ID`)
- Added `vc-dev:patch` markers per agent-guide.md §4
- `composer.json`: Lowercase package name `givanz/vvveb` for Composer compatibility
- `composer.lock`: Lock file added

### PR #10 (commit 51c358a): Prevent SITE_ID redefinition warning
- `system/core/startup.php`: Check `if (! defined('\SITE_ID'))` before defining in `start()`
- Added `vc-dev:patch` markers
- Fixes "Constant SITE_ID already defined" warning when accessing install page

### PR #12 (commits b0305f7, b0a4dca): Install flow + web installer
- `system/sites.php`: Add `?? []` null coalescing to `config('sites')` at 2 locations (lines 62, 323)
- `docker-entrypoint.sh`: 
  - Create `config/` directory with `www-data:www-data` permissions (775)
  - Default `AUTO_INSTALL=false` — NO auto CLI install on container start
  - Add `AUTO_INSTALL` env var for CI/CD opt-in
- `docker-compose.yaml`, `docker-compose.production.yaml`, `docker-compose-apache.yaml`:
  - Add `AUTO_INSTALL: "false"` to all services

## Files Changed

- `system/component/component.php`: SITE_ID global namespace fix + patch markers
- `system/core/startup.php`: SITE_ID redefinition check + patch markers  
- `system/sites.php`: Null coalescing for config('sites') at 2 locations
- `docker-entrypoint.sh`: config dir permissions + AUTO_INSTALL=false default
- `docker-compose.yaml`: AUTO_INSTALL: "false"
- `docker-compose.production.yaml`: AUTO_INSTALL: "false"
- `docker-compose-apache.yaml`: AUTO_INSTALL: "false"
- `composer.json`: Lowercase package name
- `composer.lock`: Lock file
- `vc-dev/features/vc-theme-vcenter.md`: Feature documentation

## Commits

- `50426f4` - fix: Undefined SITE_ID constant during early bootstrap (initial)
- `dace7b0` - fix: Undefined SITE_ID constant on first deploy (merged to vc-dev)
- `7ff49f9` - fix(core): fix SITE_ID namespace bug on first deploy (#8)
- `51c358a` - fix: prevent SITE_ID redefinition warning in startup.php (#10)
- `e89d16f` - docs(vc-dev): update review index — PR #10 merged
- `b0305f7` - fix: install form submit errors + prevent auto seed data + web installer flow
- `b0a4dca` - docs(vc-dev): update review index — PR #12 merged to vc-prod

## Review Records

- PR #8: `vc-dev/pr-review/completed-pr-8-...md` — APPROVED
- PR #10: `vc-dev/pr-review/completed-pr-10-prevent-site-id-redefinition.md` — APPROVED
- PR #11: `vc-dev/pr-review/completed-pr-11-release-vc-prod.md` — APPROVED
- PR #12: `vc-dev/pr-review/completed-pr-12-release-vc-prod-v2.md` — APPROVED

## Test Plan

- [x] `php -l system/sites.php` — No syntax errors
- [x] `php -l system/config.php` — No syntax errors
- [x] `php -l system/component/component.php` — No syntax errors
- [x] `php -l system/core/startup.php` — No syntax errors
- [x] Docker entrypoint syntax valid
- [x] All docker-compose files have AUTO_INSTALL=false
- [x] Fresh deploy: Container starts → no config → redirect to `/install/` → manual web installer
- [x] CI/CD auto-install: Set `AUTO_INSTALL=true` in environment

## Status

✅ **COMPLETE** — All fixes merged to vc-dev and vc-prod. Production deploy ready.