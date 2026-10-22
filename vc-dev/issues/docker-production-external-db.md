# Production dùng MySQL ngoài do Coolify quản lý

- **ID/Ticket:** `vc-dev/issues/docker-production-external-db.md`
- **Branch:** `chore/production-external-db`
- **Owner hiện tại:** agent (Kilo)
- **Trạng thái tổng:** `IN_PROGRESS`
- **Tạo ngày:** 2026-10-22 · **Cập nhật:** 2026-10-22

## Objective

Bỏ service `db` (mysql:8.0) khỏi `docker-compose.production.yaml` và dùng MySQL ngoài do Coolify
quản lý (Predefined Database) cho production.

Definition of Done:

1. `docker-compose.production.yaml` không còn service `db`, không còn volume `vvveb_db_data`,
   không còn `depends_on`.
2. Thông tin kết nối DB lấy từ Coolify Variables: `DB_HOST` (bắt buộc), `DB_PORT`, `DB_DATABASE`,
   `DB_USER` (fallback `DB_USERNAME` do Coolify inject), `DB_PASSWORD`; `DB_ENGINE` giữ `mysqli`.
3. `docker-entrypoint.sh` chờ DB sẵn sàng bằng kiểm tra `connect_errno` thật, hỗ trợ `DB_PORT`,
   in cảnh báo nếu không kết nối được.
4. Bản compose có service mysql được giữ lại ở `docker-compose.production.full.yaml` làm phương án
   rollback (không deploy file này).
5. Có script `fresh-install.sh` để reset site về trạng thái cài mới.
6. Review độc lập + PR vào `vc-prod` theo quy trình `../instructions/development.md` §6.3.

## Scope & Impact Analysis

- **Module/file ảnh hưởng:**
  - `docker-compose.production.yaml` (sửa)
  - `docker-compose.production.full.yaml` (mới — bản cấu hình cũ có service mysql)
  - `docker-entrypoint.sh` (sửa)
  - `fresh-install.sh` (mới)
  - `vc-dev/issues/README.md`, `vc-dev/instructions/skills.md` (cập nhật index + biến môi trường)
- **Mức can thiệp core (architecture.md §4.2):** mức 3 (config) — chỉ sửa file cấu hình deploy và
  script container, không đụng code PHP core.
- **Giải pháp bảo vệ code core:** không patch core. Production dùng MySQL (không chuyển SQLite) vì image
  production **không có extension `sqlite3`** — driver `system/db/sqlite.php:85` dùng class `\SQLite3`, còn
  `Dockerfile.production` chỉ `docker-php-ext-install ... mysqli ...` (không cài `sqlite3`). Lưu ý:
  `install/controller/index.php:41` liệt kê `mysqli`/`mysqlnd` trong `REQUIRED_EXTENSIONS` nhưng
  `checkRequirements()` chỉ hiển thị cảnh báo, không chặn cài đặt — nên đây không phải lý do quyết định
  engine, chỉ là yêu cầu môi trường của installer.
- **Rủi ro & giảm thiểu:**
  - Rủi ro: `DB_HOST` không được set trong Coolify Variables ⇒ app không kết nối được DB.
    Giảm thiểu: `docker compose config` in cảnh báo biến thiếu; `docker-entrypoint.sh` in
    `WARNING: Không kết nối được database <host>:<port>`; hướng dẫn set biến nằm ngay trong PR body.
  - Rủi ro: app và container DB không cùng Docker network ⇒ không resolve được hostname DB.
    Giảm thiểu: dùng "Connect to Predefined Database" của Coolify (Coolify tự inject biến DB và
    gắn app vào network chung với DB), hoặc tự khai báo network `external: true` trỏ tới network
    Coolify. Việc cấu hình network thuộc phạm vi ops, ghi rõ trong PR body.
  - Rủi ro: DB chạy port khác 3306. Giảm thiểu: thêm `DB_PORT` vào compose + entrypoint + CLI install.
  - Rủi ro: mất dữ liệu MySQL cũ khi bỏ container mysql. Giảm thiểu: volume `vvveb_db_data` không
    bị xoá (compose file cũ vẫn giữ volume), bản cấu hình cũ còn ở `.full.yaml`.
- **Ảnh hưởng khi sync upstream:** không — không sửa file core upstream.
- **Ngoài phạm vi (Out of scope):** chuyển production sang SQLite; sửa installer để prefill form từ
  env var; cấu hình Coolify resource/network (thuộc về ops).

## Step-by-Step Tasklist

- [x] COMPLETED: T1 — Tạo branch `chore/production-external-db` từ `vc-dev` (2026-10-22)
- [x] COMPLETED: T2 — Bỏ service `db` + volume `vvveb_db_data` + `depends_on` khỏi
  `docker-compose.production.yaml`, lưu bản cũ vào `docker-compose.production.full.yaml` (2026-10-22)
- [x] COMPLETED: T3 — Chuyển biến DB của compose sang Coolify Variables (`DB_HOST`, `DB_PORT`,
  `DB_DATABASE`, `DB_USER`/`DB_USERNAME`, `DB_PASSWORD`) (2026-10-22)
- [x] COMPLETED: T4 — `docker-entrypoint.sh`: chờ DB bằng `connect_errno`, hỗ trợ `DB_PORT`, cảnh báo
  khi không kết nối được, truyền `port` cho CLI install (2026-10-22)
- [x] COMPLETED: T5 — Thêm `fresh-install.sh` reset site về trạng thái cài mới (2026-10-22)
- [x] COMPLETED: T6 — Review độc lập PR #38 bằng subagent `general` (context sạch): verdict
  `APPROVED WITH FOLLOW-UPS`, không có `BLOCKER`/`MAJOR`; record tại
  `vc-dev/pr-review/pr-38-docker-production-external-db.md` (2026-10-22)
- [x] COMPLETED: T12 — Sửa lý do "bắt buộc dùng MySQL" trong plan (F4) + thêm cảnh báo cho
  `fresh-install.sh` (F5) (2026-10-22)
- [x] COMPLETED: T13 — Dọn indent `docker-entrypoint.sh` (F6) + thêm `docker-compose.production*.yaml`
  vào `.dockerignore` (F8) (2026-10-22)
- [ ] PENDING: T7 — Mở PR vào `vc-prod` và merge
- [ ] PENDING: T8 — Sync `vc-prod` về `vc-dev` bằng PR riêng

### Follow-up (từ review PR #38 — MINOR/NIT chưa fix trong PR này)

| Task | Finding | Nội dung |
|---|---|---|
| T9 | F1 | Dùng cú pháp bắt buộc của Compose `DB_HOST: ${DB_HOST:?}` / `DB_PASSWORD: ${DB_PASSWORD:?}` để Coolify chặn deploy khi biến DB rỗng (hiện chỉ warning ⇒ deploy "thành công" nhưng site không có DB, healthcheck vẫn xanh). Cần xác nhận Coolify inject `DB_HOST` vào interpolation environment trước khi áp dụng. |
| T10 | F2 | `config/` không có volume ⇒ mỗi lần recreate container mất `config/db.php` ⇒ site mở installer công khai. Cần quyết định: persist `config/` (volume + seed) hoặc chạy lại installer sau mỗi deploy + chặn `/install/`. Đây là hành vi pre-existing, không phải regression của PR #38. |
| T11 | F3 | Bổ sung bước migrate dữ liệu: production chưa có dữ liệu thật ⇒ cài mới bằng installer trên DB ngoài; đã có dữ liệu ⇒ `mysqldump` từ volume `vvveb_db_data` rồi restore vào DB ngoài. Ghi vào runbook deploy. |

## Test Plan

- [x] COMPLETED: Manual — `docker compose -f docker-compose.production.yaml config` render thành công:
  `DB_HOST=...`, `DB_PORT=3306`, `DB_USER` fallback đúng khi chỉ có `DB_USERNAME` (xem Handoff Summary)
- [x] COMPLETED: CLI/automation — `bash -n docker-entrypoint.sh` và `bash -n fresh-install.sh` không lỗi
  cú pháp
- [x] COMPLETED: CLI/automation — Kiểm tra logic chờ DB trả exit code đúng khi DB không phản hồi:
  `php -r "mysqli_report(MYSQLI_REPORT_OFF); $c = @new mysqli(...); exit($c && !$c->connect_errno ? 0 : 1);"`
  → exit 1 (vòng lặp tiếp tục chờ) thay vì coi như kết nối được
- [x] COMPLETED: Manual — `.dockerignore` loại `docker-compose.production*.yaml` khỏi build context,
  không loại `docker-entrypoint.sh`/`php.ini` (file build cần) — kiểm tra sau khi thêm F8
- [ ] PENDING: Manual — Coolify deploy thật: app kết nối được MySQL ngoài, `/install/` chạy được,
  healthcheck `curl -f http://localhost:80/` → healthy

## Handoff Summary

### Status Current Phase

- Phase: PR #38 đã merge vào `vc-dev` — Đã xong: T1, T2, T3, T4, T5, T6, T12, T13 · Còn lại: T7 (PR `vc-dev` → `vc-prod`), T8 + follow-up T9, T10, T11
- Commit cuối: xem `git log` trên branch `vc-dev`
- Test: `docker compose config` OK · `bash -n` OK · connect-check exit 1 đúng khi DB down
- Review: PR #38 — `APPROVED WITH FOLLOW-UPS`, không có `BLOCKER`/`MAJOR` (record:
  `vc-dev/pr-review/completed-pr-38-docker-production-external-db.md`)

### Key Decisions & Findings

1. **Production dùng MySQL ngoài (Coolify Predefined Database), không dùng SQLite** — quyết định của
   owner. Cơ sở kỹ thuật: driver SQLite dùng class `\SQLite3` (`system/db/sqlite.php:85`) nhưng
   `Dockerfile.production` không cài extension `sqlite3`; đồng thời bỏ service `db` mà giữ
   `DB_ENGINE=mysqli` + `DB_HOST=localhost` sẽ không kết nối được DB nào (container app không có
   MySQL server).
2. **`DB_HOST` phải lấy từ Coolify Variables, không hardcode** — hardcode `localhost` sai vì container
   app không có MySQL server; hostname của container DB chỉ resolve được khi hai container cùng
   network.
3. **Fallback `DB_USER:-${DB_USERNAME:-vvveb}`** — Coolify inject `DB_USERNAME` cho Predefined
   Database MySQL, biến `DB_USER` của repo dùng cho DB cũ; fallback giữ cả hai trường hợp.
4. **Vòng chờ DB viết lại bằng `connect_errno`** — `new mysqli(...)` trả exit code 0 khi PHP < 8.1
   (chỉ warning) nên vòng lặp cũ coi như kết nối được dù DB chưa sẵn sàng. Bản mới kiểm tra
   `connect_errno` nên phụ thuộc không vào exception behaviour của mysqli.

### Context for Next Agent

- Đã sửa/tạo: `docker-compose.production.yaml`, `docker-compose.production.full.yaml`,
  `docker-entrypoint.sh`, `fresh-install.sh`, `.dockerignore`,
  `vc-dev/issues/docker-production-external-db.md`
- Lệnh cần chạy: `docker compose -f docker-compose.production.yaml config` (kiểm tra biến DB được
  interpolate), `bash -n docker-entrypoint.sh`
- Cạm bẫy: `storage/sqlite/vvveb.db` vẫn được track trong git từ commit cũ `b0d7aa5` dù `.gitignore`
  đã loại — không commit file này (`vc-dev/README.md` §wip). `vc-dev/wip/*` cũng không commit.
- Review record của PR #38: `vc-dev/pr-review/completed-pr-38-docker-production-external-db.md` — verdict
  `APPROVED WITH FOLLOW-UPS`, không có `BLOCKER`/`MAJOR`. Follow-up `MINOR`/`NIT` được track bằng
  task T9, T10, T11 (và T12, T13 đã fix trong PR này) ngay trong file plan này.

### Next Action Items

1. Mở PR thứ hai `vc-dev` → `vc-prod` và merge (T7) — `gh pr merge <n> --admin --merge`.
2. Sync `vc-prod` về `vc-dev` bằng PR riêng (T8).
3. Xử lý follow-up T9 (biến DB bắt buộc), T10 (persist `config/db.php`), T11 (migrate dữ liệu).
