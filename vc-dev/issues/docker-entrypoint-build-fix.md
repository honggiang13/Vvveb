# Fix: Docker build fails - docker-entrypoint.sh not found in build context

- **ID/Ticket:** Docker build failure on Coolify deployment
- **Branch:** `fix/docker-entrypoint-build`
- **Owner hiện tại:** agent
- **Trạng thái tổng:** `IN_PROGRESS`
- **Tạo ngày:** 2026-10-21 · **Cập nhật:** 2026-10-21

## Objective

Fix Docker build failure: `COPY docker-entrypoint.sh /usr/local/bin/` fails with "not found" because `.dockerignore` excludes `docker-entrypoint.sh` and `php.ini`.

## Root Cause

`.dockerignore` line 20: `docker-entrypoint.sh` - excludes the entrypoint script from build context
`.dockerignore` line 21: `php.ini` - also excluded but COPY'd in Dockerfile line 25

Both files are needed in the build context for COPY commands to work.

## Fix

Remove `docker-entrypoint.sh` and `php.ini` from `.dockerignore` (or comment them out).

## Step-by-Step Tasklist

- [x] COMPLETED: T1 — Analyze `.dockerignore` and identify excluded files that are COPY'd in Dockerfile
- [x] COMPLETED: T2 — Remove `docker-entrypoint.sh` and `php.ini` from `.dockerignore`
- [x] COMPLETED: T3 � Fix pushed to git — Verify Dockerfile build locally (if possible) or push to trigger Coolify rebuild
- [ ] PENDING: T4 — Monitor Coolify deployment logs for success

## Test Plan

- [ ] Docker build succeeds past COPY docker-entrypoint.sh step
- [ ] Container starts and runs entrypoint correctly
- [ ] Vvveb install runs on first boot (config/db.php creation)
- [ ] Apache serves frontend on port 80

## Handoff Summary

### Status Current Phase
- Phase: Fix applied, awaiting Coolify rebuild
- Files changed: `.dockerignore` (removed docker-entrypoint.sh and php.ini from ignore list)

### Key Decisions & Findings
1. `.dockerignore` was excluding files that are explicitly COPY'd in Dockerfile
2. This is a common Docker anti-pattern - files in .dockerignore cannot be COPY'd

### Context for Next Agent
- Monitor Coolify deployment after push
- If build succeeds, update issue status to MERGED

### Next Action Items
1. Push fix to trigger Coolify rebuild
2. Verify deployment succeeds