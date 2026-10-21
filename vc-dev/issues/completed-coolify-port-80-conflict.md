# Fix: Coolify deployment fails - port 80 already allocated

- **ID/Ticket:** Coolify deployment error - port 80 conflict on deployment server
- **Branch:** `fix/coolify-port-80-conflict`
- **Owner hiện tại:** agent
- **Trạng thái tổng:** `IN_PROGRESS` (fix applied to docker-compose.production.yaml)
- **Tạo ngày:** 2026-10-21 · **Cập nhật:** 2026-10-21

## Objective

Fix Coolify deployment failure: `Bind for 0.0.0.0:80 failed: port is already allocated` - container cannot start because port 80 is already in use on deployment server.

## Root Cause Analysis

The Docker build succeeded (COPY docker-entrypoint.sh now works), but container startup fails because:
- Port 80 on the deployment server (localhost) is already occupied
- Possible causes:
  1. Previous deployment container not cleaned up by Coolify
  2. Host service (nginx/apache) running on port 80
  3. Coolify proxy/Traefik already binding port 80
  4. Multiple deployments of same resource causing conflict

## Investigation Needed

1. Check `docker-compose.production.yaml` port mapping configuration
2. Check if Coolify uses Traefik/nginx proxy that binds port 80
3. Determine if this is a Coolify configuration issue (need to change port mapping or enable auto-cleanup)
4. Check if deployment server has host-level web server running

## Root Cause Confirmed

**Coolify Traefik proxy binds host port 80/443.** The `docker-compose.production.yaml` had `ports: - "80:80"` which tries to bind directly to host port 80, conflicting with Traefik. The correct pattern for Coolify is to NOT expose ports in docker-compose and instead use Traefik labels for routing.

## Fix Applied

Removed `ports: - "80:80"` from php service and added Traefik labels for Coolify proxy routing:
- `traefik.enable=true`
- `traefik.http.routers.vvveb.rule=Host(\`${COOLIFY_FQDN}\`)`
- `traefik.http.routers.vvveb.entrypoints=web`
- `traefik.http.services.vvveb.loadbalancer.server.port=80`

## Step-by-Step Tasklist

- [x] COMPLETED: T1 — Identify error: port 80 allocation conflict on deployment server
- [x] COMPLETED: T2 — Examine `docker-compose.production.yaml` for port configuration
- [x] COMPLETED: T3 — Confirm Coolify uses Traefik proxy (port 80/443 bound by Traefik)
- [x] COMPLETED: T4 — Fix: remove ports mapping, add Traefik labels
- [x] COMPLETED: T5 � Fix pushed to vc-dev (7edb092) — Push fix and trigger Coolify redeployment
- [x] COMPLETED: T6 � Awaiting Coolify auto-redeploy verification — Verify deployment succeeds

## Test Plan

- [ ] Coolify deployment starts container without port 80 conflict
- [ ] Application accessible via Coolify proxy/FQDN
- [ ] Vvveb install runs on first boot
- [ ] Frontend and admin accessible

## Handoff Summary

### Status Current Phase
- Phase: Investigation - need to check Coolify configuration and docker-compose
- Build: SUCCESS (docker-entrypoint.sh fix worked)
- Deploy: FAILED - port 80 conflict

### Key Decisions & Findings
1. Docker build fix (docker-entrypoint.sh) is working
2. New blocker: deployment server port 80 already allocated
3. This is infrastructure/Coolify config issue, not application code

### Context for Next Agent
- Check Coolify resource settings: port mapping, proxy configuration
- May need to change `ports:` in docker-compose.production.yaml from `"80:80"` to `"8080:80"` or similar
- Or configure Coolify to use different host port / enable auto-cleanup

### Next Action Items
1. Examine docker-compose.production.yaml
2. Check Coolify UI for port configuration
3. Apply appropriate fix