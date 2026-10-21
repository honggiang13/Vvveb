# Fix: Undefined SITE_ID constant on first deploy

- **ID/Ticket:** Runtime fatal error - Undefined constant "Vvveb\System\Component\SITE_ID"
- **Branch:** `fix/undefined-site-id-first-deploy`
- **Owner hiện tại:** agent
- **Trạng thái tổng:** `MERGED` (commit 50426f4 pushed to vc-dev)
- **Tạo ngày:** 2026-10-21 · **Cập nhật:** 2026-10-21

## Objective

Fix fatal error on first deploy: `Undefined constant "Vvveb\System\Component\SITE_ID"` in `system/component/component.php:180`

## Root Cause

`Component::loadComponents()` uses `SITE_ID` constant at lines 180, 268, 278, but this constant is only defined in `startup.php` at line 406/418 inside `FrontController::dispatch()`.

The Component class is instantiated and `loadComponents()` is called during View construction (which happens during the first request), but `SITE_ID` is only defined later in `FrontController::dispatch()` at line 406/418.

During first deploy:
1. Container starts, entrypoint runs install (creates site_id=1 in config/sites.php)
2. First HTTP request comes in
2. View is instantiated → Component::getInstance() → loadComponents() called
3. loadComponents() tries to use SITE_ID constant
4. But startup.php hasn't reached the SITE_ID definition yet (it's in FrontController::dispatch)
5. **Fatal error: Undefined constant SITE_ID**

## Fix

Added fallback definition in `system/component/component.php`:

```php
if (! defined('SITE_ID')) {
    define('SITE_ID', 1);
}
```

This ensures SITE_ID has a default value (1) if Component loads before startup.php defines it.

## Files Changed

- `system/component/component.php`: Added fallback define('SITE_ID', 1)

## Commits

- `50426f4` - fix: Undefined SITE_ID constant during early bootstrap

## Test Plan

- [ ] First deploy: container starts, install runs, first request succeeds
- [ ] No "Undefined constant SITE_ID" error in logs
- [ ] Site loads correctly with site_id=1
- [ ] Admin panel accessible

## Status

✅ Fix committed and pushed to vc-dev (commit 50426f4)