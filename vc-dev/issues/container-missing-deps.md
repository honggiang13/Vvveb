# Fix: Container fails - missing vendor/autoload.php and runtime directories

- **ID/Ticket:** Container startup fatal error - missing composer dependencies and runtime directories
- **Branch:** `fix/container-missing-deps`
- **Owner hiện tại:** agent
- **Trạng thái tổng:** `MERGED` (commit c248db4 pushed to vc-dev)
- **Tạo ngày:** 2026-10-21 · **Cập nhật:** 2026-10-21

## Objective

Fix container startup fatal errors:
1. `Warning: require(/var/www/html//vendor/autoload.php): Failed to open stream` - Composer dependencies not installed
2. `Warning: scandir(/var/www/html/storage/cache/): Failed to open directory` - Runtime directories missing
3. `Fatal error: Undefined constant "Vvveb\System\Component\SITE_ID"` - Likely cascading from missing vendor/autoload.php

## Root Cause

**Dockerfile missing:**
1. `composer install` - No composer dependencies installed in image
2. Runtime directory creation - `storage/cache`, `storage/compiled-templates`, `public/page-cache` not created

**Entrypoint issues:**
1. Tries to clear cache directories that don't exist yet
3. Runs `php cli.php install` before composer dependencies available
5. No directory creation for `storage/cache`, `storage/compiled-templates`, `public/page-cache`, `storage/model`

## Fix Required

**Dockerfile.production:**
1. Install composer
2. Run `composer install --no-dev --optimize-autoloader` after COPY
3. Create runtime directories with proper permissions

**docker-entrypoint.sh:**
1. Create runtime directories before cache clearing
2. Run composer install if vendor missing (for first boot)
3. Run install after composer install

## Step-by-Step Tasklist

- [x] COMPLETED: T1 — Analyze errors and identify root causes
- [x] COMPLETED: T2 — Update Dockerfile.production: add composer install + directory creation
- [x] COMPLETED: T3 — Update docker-entrypoint.sh: create dirs, run composer install, fix order
- [x] COMPLETED: T4 — Fix pushed to vc-dev (c248db4), Coolify auto-redeploy triggered
- [ ] PENDING: T5 — Verify container starts and serves application (await Coolify logs)

## Test Plan

- [ ] Docker build succeeds
- [ ] Container starts without fatal errors
- [ ] Vvveb install runs on first boot (config/db.php creation)
- [ ] Frontend and admin accessible
- [ ] No "vendor/autoload.php" or "storage/cache" errors in logs

## Handoff Summary

### Status Current Phase
- Phase: Fixing Dockerfile and entrypoint
- Build: SUCCESS (previous fixes)
- Deploy: FAILED - missing vendor + runtime dirs

### Key Decisions & Findings
1. Container has no vendor/ directory - composer install missing from Dockerfile
2. Runtime directories (storage/cache, etc.) not created in image
3. Entrypoint tries to clear non-existent directories
4. Install runs before vendor available - cascading failures

### Context for Next Agent
- Add composer to Dockerfile build stage
- Create runtime directories in Dockerfile (not just entrypoint - they need to exist in image)
- Ensure entrypoint runs composer install on first boot if vendor missing

### Next Action Items
1. Update Dockerfile.production with composer install + mkdir
2. Update docker-entrypoint.sh with directory creation + composer check
3. Push and verify Coolify redeployment