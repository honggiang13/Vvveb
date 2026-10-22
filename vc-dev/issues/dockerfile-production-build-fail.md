# Fix build production fail (chmod vendor + USER www-data + CRLF shebang)

- **ID/Ticket:** `vc-dev/issues/dockerfile-production-build-fail.md`
- **Branch:** `fix/dockerfile-production-build-permissions`
- **Owner hiện tại:** agent (Kilo)
- **Trạng thái tổng:** `IN_PROGRESS`
- **Tạo ngày:** 2026-10-22 · **Cập nhật:** 2026-10-22

## Objective

Coolify deploy `vc-prod` fail ở bước build image:

```
2026-Oct-22 16:53:06 failed to solve: process "/bin/sh -c mkdir -p storage/cache ... && chown -R www-data:www-data ... && chmod -R 755 public app config system rest graphql plugins vendor" did not complete successfully: exit code: 1
```

Definition of Done:

1. `docker build -f Dockerfile.production .` chạy thành công (đã verify local bằng Docker 29.5.3).
2. Container khởi động được bằng image vừa build, Apache trả HTTP 200, app (user `www-data`) ghi được
   `storage/`, `config/`, `public/media/`, `public/page-cache/`.
3. Không phụ thuộc line ending của file khi build (script entrypoint luôn chạy được).
4. Review độc lập + PR vào `vc-dev`, sau đó PR `vc-dev` → `vc-prod`.

## Root cause (đã verify bằng lệnh thật)

Có 3 lỗi riêng biệt trong `Dockerfile.production`, tất cả đều từ commit `7ed91bb`:

1. **`chmod ... vendor` chạy trước khi `vendor/` tồn tại.** `.dockerignore` loại `vendor/` khỏi build
   context, còn `composer install` nằm ở step *kế tiếp* → `chmod` báo
   `chmod: cannot access '/var/www/html/vendor': No such file or directory` và trả exit code 1.
   Verify: `docker run --rm -u www-data php:8.3-apache sh -c "chmod -R 755 /var/www/html/vendor"` →
   lỗi trên.
2. **`USER www-data` đặt trước các step cần quyền root.** `COPY docker-entrypoint.sh /usr/local/bin/`
   tạo file root-owned → `RUN chmod +x /usr/local/bin/docker-entrypoint.sh` chạy dưới user `www-data`
   → `Operation not permitted` → exit 1. Verify: build local lần 2 dừng đúng ở step này:
   `ERROR: process "/bin/sh -c chmod +x /usr/local/bin/docker-entrypoint.sh" did not complete successfully: exit code: 1`.
3. **CRLF làm hỏng shebang khi build trên Windows — KHÔNG phải nguyên nhân fail Coolify.** Coolify clone
   repo trên Linux nên blob `docker-entrypoint.sh` trong git luôn là LF (`git ls-files --eol` →
   `i/lf`); chỉ khi checkout trên Windows (`core.autocrlf=true`) file trong working tree mới có CRLF
   (66 dòng) → container báo `exec /usr/local/bin/docker-entrypoint.sh: no such file or directory`
   (shebang `#!/bin/bash\r`). Fix `.gitattributes` + `sed` trong Dockerfile vẫn đúng (bảo vệ build
   local/CI), nhưng không giải quyết lỗi deploy Coolify.

Lưu ý: `mkdir`/`chown` chạy dưới `www-data` **không** phải nguyên nhân — `/var/www/html` trong image
`php:8.3-apache` là `drwxrwxrwt www-data www-data` nên `www-data` tạo được thư mục và `chown` được
file của chính mình (verify bằng `docker run --rm -u www-data php:8.3-apache sh -c "mkdir -p ... ; chown -R www-data:www-data ..."` → exit 0).

## Scope & Impact Analysis

- **Module/file ảnh hưởng:** `Dockerfile.production`, `.gitattributes`.
- **Mức can thiệp core (architecture.md §4.2):** ngoài thang đo tính năng (mức 1–6) — chỉ build/deploy
  config (`Dockerfile.production`, `.gitattributes`), không sửa file PHP core.
- **Giải pháp bảo vệ code core:** không patch core.
- **Rủi ro & giảm thiểu:**
  - Rủi ro: đổi thứ tự `USER` làm thay đổi user chạy các step build. Giảm thiểu: mọi step build
    (mkdir/chown/chmod/composer/copy entrypoint) chạy as root như trước `7ed91bb`; `USER www-data` chỉ
    áp dụng cho runtime (đặt cuối file, trước `ENTRYPOINT`).
  - Rủi ro: `composer install` chạy as root ⇒ `vendor/` root-owned. Giảm thiểu: `chown -R
    www-data:www-data vendor` ngay sau đó (đã verify trong image: `vendor` thuộc www-data).
  - Rủi ro: file `.sh` trong repo bị đổi line ending khi người khác checkout. Giảm thiểu: thêm
    `*.sh text eol=lf` vào `.gitattributes` + `sed -i 's/\r$//'` trong Dockerfile (2 lớp).
- **Ảnh hưởng khi sync upstream:** không — không sửa file core upstream.
- **Ngoài phạm vi (Out of scope):** sửa `Dockerfile` dev (đã verify hoạt động đúng: `/var/www/html`
  writable, `chmod` list không có `vendor`); đổi user runtime của container.

## Step-by-Step Tasklist

- [x] COMPLETED: T1 — Reproduce lỗi build local bằng Docker Desktop (đã bật daemon) (2026-10-22)
- [x] COMPLETED: T2 — Xác định 3 root cause: `chmod vendor` sớm, `USER www-data` đặt sai chỗ, CRLF
  shebang (2026-10-22)
- [x] COMPLETED: T3 — Sửa `Dockerfile.production`: `chmod vendor` chạy sau `composer install` +
  `chown vendor`; `USER www-data` chuyển xuống cuối file; `sed -i 's/\r$//'` cho entrypoint (2026-10-22)
- [x] COMPLETED: T4 — Thêm `*.sh text eol=lf` vào `.gitattributes` (2026-10-22)
- [x] COMPLETED: T5 — Build local thành công (`docker build -f Dockerfile.production .` → OK) (2026-10-22)
- [x] COMPLETED: T6 — Chạy container từ image vừa build: Apache trả HTTP 200, app ghi được
  `storage/`, `config/`, `public/media/`, `public/page-cache/` (2026-10-22)
- [x] COMPLETED: T7 — Review độc lập PR #40 bằng subagent `general` (context sạch): verdict
  `APPROVED WITH FOLLOW-UPS`, không có `BLOCKER`/`MAJOR`; reviewer tự build `--no-cache` và chạy
  container để verify. Record: `vc-dev/pr-review/pr-40-dockerfile-production-build-fail.md` (2026-10-22)
- [x] COMPLETED: T8 — PR #40 (`fix/dockerfile-production-build-permissions` → `vc-dev`) đã merge (merge commit `b6cfe36`) (2026-10-22)
- [ ] PENDING: T9 — PR `vc-dev` → `vc-prod` và merge (deploy lại trên Coolify)

### Follow-up (từ review PR #40)

| Task | Finding | Nội dung |
|---|---|---|
| T10 | F1 | Mô tả root cause trong PR body/plan đã sửa ở PR này (CRLF không phải nguyên nhân fail Coolify). Task giữ để nhắc: khi có log lỗi build, phải phân biệt lỗi do repo với lỗi do môi trường build. |
| T11 | F2 | Nhãn mức can thiệp core trong plan/PR đã sửa ở PR này ("ngoài thang đo core"). Giữ để thống nhất cách ghi trong các plan sau. |
| T12 | F3 | `Dockerfile` dev (`php:8.3-fpm`) vẫn đặt `USER www-data` trước step cần root. Đã verify **không** fail (image fpm có `/var/www/html` là `drwxrwxrwt www-data`, không có `vendor`/composer/entrypoint-chmod), nhưng pattern rủi ro nếu thêm `composer install`/entrypoint sau này → nên dời `USER www-data` xuống cuối file ở PR sau. |

## Test Plan

- [x] COMPLETED: CLI/automation — `docker build -f Dockerfile.production -t vvveb-prod-test:latest .`
  → build thành công (trước fix: fail ở step chmod vendor; sau fix 1: fail ở step `chmod +x
  docker-entrypoint.sh`; sau fix 2: build OK)
- [x] COMPLETED: CLI/automation — `docker run -d -p 18081:80 vvveb-prod-test:latest` → log cho thấy
  entrypoint chạy (`config/db.php không tồn tại - container sẽ redirect về /install/`) và Apache
  `AH00163 ... resuming normal operations`
- [x] COMPLETED: Manual — `Invoke-WebRequest http://localhost:18081/` → HTTP 200
- [x] COMPLETED: Manual — `docker exec ... touch storage/cache/_t config/_t public/page-cache/_t
  public/media/_t` → tất cả WRITE_OK (app chạy user `www-data` uid=33)
- [x] COMPLETED: Manual — `ls -ld` trong container: `storage`, `config`, `vendor`, `public`, `app` đều
  thuộc `www-data`; `/usr/local/bin/docker-entrypoint.sh` có quyền `-rwxr-xr-x`
- [ ] PENDING: Manual — Coolify deploy `vc-prod` build + start thành công, healthcheck xanh

## Handoff Summary

### Status Current Phase

- Phase: fix build production — Đã xong: T1, T2, T3, T4, T5, T6 · Còn lại: T7, T8, T9
- Test: build local OK · container chạy OK · HTTP 200 · quyền ghi OK

### Key Decisions & Findings

1. **Chỉ sửa `Dockerfile.production`, không sửa `Dockerfile` dev** — image dev (`php:8.3-fpm`) đã verify
   `/var/www/html` writable bởi `www-data` và không có `vendor` trong danh sách chmod nên build được
   (theo dõi ở T12).
2. **`USER www-data` giữ ở runtime, đặt cuối file** — Apache vẫn bind được port 80 dưới user
   `www-data` (đã verify: container trả HTTP 200), nên không cần đổi sang root.
3. **`sed -i 's/\r$//'` trong Dockerfile** — lớp bảo vệ thứ hai sau `.gitattributes`, để build không
   phụ thuộc line ending của máy build.
4. **Chỉ 2 root cause gây fail Coolify** (`chmod vendor` trước `composer install`, `USER www-data` đặt
   trước step cần root); CRLF là vấn đề build local trên Windows, không phải nguyên nhân deploy fail.

### Context for Next Agent

- Đã sửa: `Dockerfile.production`, `.gitattributes`
- Lệnh kiểm tra: `docker build -f Dockerfile.production -t vvveb-prod-test:latest .` rồi
  `docker run -d --name vvveb-test -p 18081:80 vvveb-prod-test:latest` và `docker logs vvveb-test`
- Cạm bẫy: `docker build` cần Docker daemon (đã bật qua `docker desktop start`); build đầu tiên lâu
  (~5 phút, compile PHP extension), các lần sau dùng cache.
- Cạm bẫy: `git add --renormalize .` sẽ đụng hàng loạt file `.sql` (do core.autocrlf=true) — chỉ
  dùng `git checkout-index -f -- <file .sh>` sau khi thêm `.gitattributes`.

### Next Action Items

1. Review độc lập PR (T7) — subagent `general`, context sạch.
2. PR vào `vc-dev` (T8) rồi PR `vc-dev` → `vc-prod` (T9) — `gh pr merge <n> --admin --merge`.
3. Deploy lại trên Coolify và xác nhận healthcheck.
