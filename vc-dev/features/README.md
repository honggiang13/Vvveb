# `vc-dev/features/` — Plan & Tasklist cho tính năng mới

Mỗi tính năng mới **bắt buộc** có 1 file plan tại đây **trước khi** bắt đầu code.

## Quy ước

- Tên file: kebab-case, ngắn, không dấu. Ví dụ: `loyalty-points.md`, `multi-currency.md`.
- Một tính năng = một file. Không gộp nhiều tính năng vào cùng file.
- Không xóa file khi tính năng hoàn thành — giữ lại làm lịch sử (`Trạng thái tổng: MERGED`).
- Sau khi implement xong và PR đã merge vào `vc-dev`, đổi tên file plan bằng cách thêm tiền tố `completed-` (ví dụ `completed-loyalty-points.md`). Cập nhật bảng danh sách tương ứng.
- **Bắt buộc review độc lập trước khi merge:** kết quả review lưu tại `../pr-review/pr-<number>-<slug>.md` (quy trình `../instructions/development.md` §6.3).

## Template

Copy từ `../instructions/development.md` §4.2. Cấu trúc tối thiểu bắt buộc:

```markdown
# <Tên feature>
- **ID/Ticket:** … · **Branch:** `feature/<name>` · **Owner hiện tại:** … 
- **Trạng thái tổng:** PLANNING | IN_PROGRESS | REVIEW | MERGED | BLOCKED
- **Tạo ngày:** YYYY-MM-DD · **Cập nhật:** YYYY-MM-DD

## Objective
## Scope & Impact Analysis
## Step-by-Step Tasklist
- [ ] PENDING: T1 — …
## Test Plan
## Handoff Summary
```

## Danh sách tính năng

| File | Trạng thái | Branch | Ghi chú |
|---|---|---|---|
| vc-import-csv.md | PLANNING | feature/vc-import-csv | Port plugin VCenter Import CSV từ source |
| vc-theme-vcenter.md | PLANNING | feature/vc-theme-vcenter | Theme mới VCenter theo UI/UX nguồn tenant (không mang phụ thuộc hệ thống cũ) |
| i18n-vietnamese.md | PLANNING | feature/i18n-vietnamese | Thêm ngôn ngữ Tiếng Việt + chuẩn bị nội dung translations (không sửa core) |

> Cập nhật bảng này mỗi khi tạo hoặc merge một file plan.

## Liên quan

- `../instructions/development.md` §4 — quy trình Planning, Tasklist & Multi-Agent Handoff.
- `../instructions/architecture.md` §4.2 — mức can thiệp core.
- `../instructions/agent-guide.md` — ràng buộc cho agent.
