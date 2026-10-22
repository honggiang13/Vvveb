# `vc-dev/issues/` — Plan & Tasklist cho fix bug / cải tiến

Mỗi bug fix, cải tiến nhỏ hoặc nợ kỹ thuật **bắt buộc** có 1 file plan tại đây
**trước khi** bắt đầu code.

## Quy ước

- Tên file: `<issue-id>-<slug>.md` hoặc `<slug>.md` nếu không có ticket.
  Ví dụ: `12-checkout-currency.md`, `sync-upstream-2026-10-21.md`.
- Một issue = một file. Bug phát hiện trong lúc làm việc khác ⇒ tạo file mới, không nhét vào plan khác.
- Không xóa file khi đã fix — giữ lại làm lịch sử (`Trạng thái tổng: MERGED`).
- **Bắt buộc review độc lập trước khi merge:** kết quả review lưu tại `../pr-review/pr-<number>-<slug>.md` (quy trình `../instructions/development.md` §6.3).

## Template

Copy từ `../instructions/development.md` §4.2 (giống feature, chỉ khác vị trí thư mục).

## Danh sách issue

| File | Trạng thái | Branch | Ghi chú |
|---|---|---|---|
| `completed-migrate-to-vendored-repo.md` | `MERGED` | `chore/migrate-to-vendored-repo-v2` | Vendor hóa 7 thành phần, bỏ submodule (Phương án D). **Ưu tiên #1** — merge commit `a47abff` (PR #6). |
| `completed-coolify-cicd.md` | `MERGED` | `feature/coolify-cicd` | CI/CD Coolify deploy production. Merge commit `a4d1900` (PR #4). |
| `dockerfile-production-build-fail.md` | `IN_PROGRESS` | `fix/dockerfile-production-build-permissions` | Fix build production Coolify fail: `chmod vendor` chạy trước `composer install`, `USER www-data` đặt trước step cần root, CRLF làm hỏng shebang entrypoint. |
| `docker-production-external-db.md` | `MERGED` | `chore/production-external-db` | Bỏ service mysql khỏi `docker-compose.production.yaml`, production dùng MySQL ngoài do Coolify quản lý (`DB_HOST` từ Coolify Variables). Kèm `fresh-install.sh`. |
| `docker-entrypoint-build-fix.md` | `MERGED` | `fix/docker-entrypoint-build` (direct to vc-dev) | Fix Docker build: docker-entrypoint.sh & php.ini excluded by .dockerignore. Commit `447b9ad`. |
| `coolify-port-80-conflict.md` | `MERGED` | (direct to vc-dev) | Fix Coolify deploy port 80 conflict: remove ports mapping, add Traefik labels. Commit `7edb092`. |
| `container-missing-deps.md` | `MERGED` | (direct to vc-dev) | Fix container startup: add composer install, runtime dirs, fix entrypoint. Commit `c248db4`. |
| `undefined-site-id-first-deploy.md` | `MERGED` | `fix/undefined-site-id-first-deploy-v2`, `fix/undefined-site-id-startup-v2` → PR #8, #10, #12 | Fix SITE_ID namespace bug + install flow errors + prevent auto seed data. Commits: 50426f4, 7ff49f9 (PR #8), 51c358a (PR #10), b0305f7 (PR #12). Files: component.php, startup.php, sites.php, docker-entrypoint.sh, docker-compose*.yaml. |

> Cập nhật bảng này mỗi khi tạo hoặc merge một file plan.

## Việc nền tảng đã biết (chưa có plan file — tạo khi bắt tay làm)

| # | Mô tả | Mức can thiệp core | Ghi chú |
|---|---|---|---|
| 1 | **Migration Phương án D**: vendor hóa 7 thành phần còn là submodule rỗng (landing, admin/default, blog-default, contact-form, payment, shipping, tinymce-dist) thành file thường; xóa `.gitmodules` | n/a (không sửa code PHP) | **ĐÃ HOÀN TẤT** — merge commit `a47abff` (PR #6). Điều kiện tiên quyết để chạy được hệ thống. |
| 2 | Ghi nhận commit nguồn vào bảng theo dõi vendor sau migration | n/a | Nằm trong `completed-migrate-to-vendored-repo.md` (T7). `../instructions/architecture.md` §2.5 — bắt buộc, tránh vendor drift |
| 3 | Rotate & tách secret khỏi `config/app.php` (`key`, `cronkey` đang nằm trong git) | mức 3 (config) | Rủi ro bảo mật nếu fork được public. **Chưa có plan file** |
| 4 | Thống nhất `V_VERSION` giữa `index.php` (0.0.7) và `cli.php` (0.0.1) | mức 6 (patch core) | Inconsistency có sẵn của upstream. **Chưa có plan file** |
| 5 | Không có test framework / CI ⇒ mọi PR phải test thủ công | n/a | Xem `../instructions/skills.md` §8. **Chưa có plan file** |
| 6 | `honggiang13/vvveb-landing` mới đổi tên từ `honggiang13/landing` — xác nhận mọi tài liệu/script dùng tên mới | n/a | GitHub tự redirect URL cũ, nhưng nên chuẩn hóa tên mới. Đã chuẩn hóa trong docs (PR #1); **thư mục local cũng đã đổi** `..\landing` → `..\vvveb-landing` (2026-10-21) |
| 7 | Divergence với upstream ở tầng repo (upstream còn dùng submodule) — cần quy trình xử lý mỗi lần merge `upstream/master` | n/a | Phát sinh sau migration; bổ sung vào `../instructions/development.md` §2.4 khi gặp lần đầu. **Chưa có plan file** |

## Liên quan

- `../instructions/development.md` §4 — quy trình Planning, Tasklist & Multi-Agent Handoff.
- `../instructions/architecture.md` §2 — chiến lược vendor hóa & trạng thái repo phụ.
- `../instructions/agent-guide.md` §10 — quy trình khi bị chặn (BLOCKED).
