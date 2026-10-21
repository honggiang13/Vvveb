# `vc-dev/issues/` — Plan & Tasklist cho fix bug / cải tiến

Mỗi bug fix, cải tiến nhỏ hoặc nợ kỹ thuật **bắt buộc** có 1 file plan tại đây
**trước khi** bắt đầu code.

## Quy ước

- Tên file: `<issue-id>-<slug>.md` hoặc `<slug>.md` nếu không có ticket.
  Ví dụ: `12-checkout-currency.md`, `sync-upstream-2026-10-21.md`.
- Một issue = một file. Bug phát hiện trong lúc làm việc khác ⇒ tạo file mới, không nhét vào plan khác.
- Không xóa file khi đã fix — giữ lại làm lịch sử (`Trạng thái tổng: MERGED`).

## Template

Copy từ `../instructions/development.md` §4.2 (giống feature, chỉ khác vị trí thư mục).

## Danh sách issue

| File | Trạng thái | Branch | Ghi chú |
|---|---|---|---|
| _(chưa có)_ | — | — | — |

> Cập nhật bảng này mỗi khi tạo hoặc merge một file plan.

## Việc nền tảng đã biết (chưa có plan file — tạo khi bắt tay làm)

| # | Mô tả | Mức can thiệp core | Ghi chú |
|---|---|---|---|
| 1 | **Migration Phương án D**: vendor hóa 7 thành phần còn là submodule rỗng (landing, admin/default, blog-default, contact-form, payment, shipping, tinymce-dist) thành file thường; xóa `.gitmodules` | n/a (không sửa code PHP) | Điều kiện tiên quyết để chạy được hệ thống. Checklist: `../instructions/architecture.md` §2.6. Đây là **task ưu tiên #1**. |
| 2 | Ghi nhận commit nguồn vào bảng theo dõi vendor sau migration | n/a | `../instructions/architecture.md` §2.5 — bắt buộc, tránh vendor drift |
| 3 | Rotate & tách secret khỏi `config/app.php` (`key`, `cronkey` đang nằm trong git) | mức 3 (config) | Rủi ro bảo mật nếu fork được public |
| 4 | Thống nhất `V_VERSION` giữa `index.php` (0.0.7) và `cli.php` (0.0.1) | mức 6 (patch core) | Inconsistency có sẵn của upstream |
| 5 | Không có test framework / CI ⇒ mọi PR phải test thủ công | n/a | Xem `../instructions/skills.md` §8 |
| 6 | `honggiang13/vvveb-landing` mới đổi tên từ `honggiang13/landing` — xác nhận mọi tài liệu/script dùng tên mới | n/a | GitHub tự redirect URL cũ, nhưng nên chuẩn hóa tên mới |

## Liên quan

- `../instructions/development.md` §4 — quy trình Planning, Tasklist & Multi-Agent Handoff.
- `../instructions/architecture.md` §2 — chiến lược vendor hóa & trạng thái repo phụ.
- `../instructions/agent-guide.md` §10 — quy trình khi bị chặn (BLOCKED).
