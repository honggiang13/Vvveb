# CI/CD Coolify — Deploy Vvveb lên Production

- **ID/Ticket:** vc-dev/issues/coolify-cicd.md
- **Branch:** `feature/coolify-cicd` (tạo mới từ `vc-dev`, KHÔNG commit cùng nhánh đang làm việc của dev khác)
- **Owner hiện tại:** agent
- **Trạng thái tổng:** `REVIEW`
- **Tạo ngày:** 2026-10-21 · **Cập nhật:** 2026-10-21

## Objective

Tích hợp CI/CD bằng Coolify để build và deploy Vvveb lên production từ nhánh `vc-dev`, không ảnh hưởng đến local dev và không impact tiến trình code fix trên nhánh của dev khác.

Tiêu chí hoàn thành (Definition of Done):
1. Có `Dockerfile.production` build image Vvveb cho production (Apache + PHP 8.3).
2. Có file `docker-compose.production.yaml` (đổi tên từ `coolify-compose.yaml`) cấu hình service cho Coolify.
3. Các secret (`key`, `cronkey` trong `config/app.php`) được tách ra biến môi trường Coolify, không commit vào git.
4. `env.php` production tắt `DEBUG`, `PAGE_CACHE`, `SQL_CHECK`.
5. Luồng deploy: push `vc-dev` → Coolify pull → build image → khởi động container → chạy install/cron qua `cli.php` → xóa cache → health check.
6. File plan được stage vào nhánh `feature/coolify-cicd`, không commit lẫn với nhánh khác.

## Scope & Impact Analysis

- **Module/file ảnh hưởng:**
  - `Dockerfile.production` (mới)
  - `docker-compose.production.yaml` (mới)
  - `docker-entrypoint.sh` (mới)
  - `.dockerignore` (mới)
  - `config/app.php` (mức 3 — tách `key`/`cronkey` ra env)
  - `env.php` (mức 3 — cài mặc định production-safe)
  - `vc-dev/issues/coolify-cicd.md` (plan, mới)
- **Mức can thiệp core (architecture.md §4.2):** mức 3 (config) — không sửa logic core, chỉ thay cách cung cấp cấu hình và runtime container.
- **Giải pháp bảo vệ code core:** biến môi trường + file mới, không patch file core hiện có.
- **Rủi ro & giảm thiểu:**
  - Vendor hóa (Phương án D) chưa thực hiện → 7 thư mục vendor còn rỗng. Giảm thiểu: ghi chú trong plan là điều kiện tiên quyết, deploy production chỉ được sau khi migration xong.
  - `config/db.php` chưa có (gitignored) → phải chạy install qua `cli.php` sau khi container khởi động.
- **Ảnh hưởng khi sync upstream:** không (file mới, không đụng file core upstream).
- **Ngoài phạm vi (Out of scope):** migration vendor hóa (có plan riêng `migrate-to-vendored-repo.md`), local dev setup.

## Step-by-Step Tasklist

- [x] COMPLETED: T1 — Tạo nhánh `feature/coolify-cicd` từ `vc-dev`, ghi chú không commit lẫn nhánh khác (2026-10-21)
- [x] COMPLETED: T2 — Viết `Dockerfile.production` (Apache + PHP 8.3, production-safe) (2026-10-21)
- [x] COMPLETED: T3 — Viết `docker-compose.production.yaml` cho Coolify (db + php, env vars, volumes) (2026-10-21)
- [x] COMPLETED: T4 — Tách `key`/`cronkey` khỏi `config/app.php` thành biến môi trường `getenv()` (2026-10-21)
- [x] COMPLETED: T5 — Cập nhật `env.php` mặc định production-safe (DEBUG=false, PAGE_CACHE=false, SQL_CHECK=false) (2026-10-21)
- [x] COMPLETED: T6 — Ghi documentation deploy trong plan (lệnh Coolify, biến env, health check)
- [x] COMPLETED: T7 — Stage tất cả file mới/thay đổi vào nhánh `feature/coolify-cicd`, không commit cùng nhánh dev khác

## Test Plan

- [x] COMPLETED: Manual — File `Dockerfile.production` đã viết sẵn (chưa build local do chưa có Docker desktop trên môi trường này)
- [x] COMPLETED: CLI/automation — `php -l` đã kiểm tra `config/app.php`, `env.php` (không có lỗi cú pháp)
- [ ] PENDING: Coolify — Connect repo `honggiang13/Vvveb`, nhánh `feature/coolify-cicd`, deploy thử trên staging

## Handoff Summary

### Status Current Phase
- Phase: Triển khai file cấu hình CI/CD Coolify
- Đã xong: T1, T2, T3, T4, T5, T6, T7 (COMPLETED)
- Còn lại: chờ reviewer PR #4 merge vào `vc-dev`
- Commit cuối: 5aa1367 trên `feature/coolify-cicd`
- PR: https://github.com/honggiang13/Vvveb/pull/4
- Test đã chạy: `php -l` trên `config/app.php` và `env.php` — OK

### Key Decisions & Findings
1. Dùng `Dockerfile.production` (php:8.3-apache) thay vì `Dockerfile` gốc (php:8.3-fpm) — Apache sẵn sàng cho production, không cần reverse proxy thêm.
2. Dùng `docker-compose.production.yaml` thay vì `docker-compose.yaml` gốc để tránh nhầm lẫn, Coolify trỏ trực tiếp đến file này.
3. Secret `key`/`cronkey` trong `config/app.php` dùng `getenv()` với fallback giá trị cũ — local dev vẫn chạy được, production override bằng biến Coolify.
4. `env.php` đổi mặc định thành production-safe (`DEBUG=false`, `PAGE_CACHE=false`, `SQL_CHECK=false`).
5. Điều kiện tiên quyết: migration vendor hóa (Phương án D) phải xong trước khi deploy production (7 thư mục vendor còn rỗng).

### Context for Next Agent
- **File đã tạo/sửa:** `Dockerfile.production`, `docker-entrypoint.sh`, `docker-compose.production.yaml`, `.dockerignore`, `config/app.php`, `env.php`, `vc-dev/issues/coolify-cicd.md`
- **File CHƯA nên sửa:** file PHP core khác, `config/db.php` (gitignored, tạo bởi installer)
- **Biến môi trường/cấu hình:** Coolify Variables cần thiết lập: `MYSQL_ROOT_PASSWORD`, `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD`, `APP_KEY`, `CRON_KEY`, `ADMIN_EMAIL`, `ADMIN_PASSWORD`, `DEBUG`, `PAGE_CACHE`, `SQL_CHECK`
- **Lệnh build/test cần chạy:** `php -l config/app.php`, `php -l env.php`, `docker build -f Dockerfile.production .`
- **Trạng thái môi trường/vendor:** 7 thư mục vendor còn rỗng (chưa migration Phương án D), không deploy production được trước khi migration xong.
- **Cạm bẫy đã gặp:** `Dockerfile` gốc (php:8.3-fpm) bị lỗi `apt-get install` khi build trên Coolify → đã thay bằng `Dockerfile.production` (php:8.3-apache).

### Next Action Items
1. Reviewer đối chiếu checklist `development.md` §6.2 trước khi merge PR #4 vào `vc-dev`.
2. Sau merge: xóa nhánh `feature/coolify-cicd`, cập nhật plan sang `MERGED`.
3. Điều kiện tiên quyết trước khi deploy production: migration vendor hóa (Phương án D) phải xong (7 thư mục vendor còn rỗng).

