# PR Review Records

Kết quả review **độc lập** cho các PR vào `vc-dev`. Quy trình đầy đủ: `vc-dev/instructions/development.md` §6.3.

## Quy tắc bắt buộc

1. **Độc lập:** reviewer phải là session/agent **khác tác giả** PR (context sạch). Không tự review bài mình viết.
2. **Xác minh:** mỗi claim quan trọng phải kiểm bằng lệnh thật (git, gh, đọc file nguồn) — không rubber-stamp theo diff/description.
3. **File bắt buộc:** mọi PR phải có `vc-dev/pr-review/pr-<number>-<slug>.md` **trước khi merge**. Không có file ⇒ không merge. **Sau khi PR merge**, đổi tên file review thành `completed-pr-<number>-<slug>.md` và cập nhật index.
4. **Verdict:** `APPROVED` | `APPROVED WITH FOLLOW-UPS` | `CHANGES REQUIRED` | `HOLD`. Chỉ 2 verdict đầu được merge.
5. **Fix rồi mới merge:** `BLOCKER`/`MAJOR` phải đóng; người viết **tự xác minh lại** từng finding trước khi sửa (reviewer có thể sai — nếu sai, ghi phản biện vào file).
6. **Cập nhật index:** thêm dòng vào bảng dưới đây khi tạo file mới; **sau merge** đổi tên file thành `completed-pr-<number>-<slug>.md` và cập nhật bảng index.
7. **Model thay thế:** nếu model/agent được chỉ định (vd Kilocode) không khả dụng, dùng subagent `general` (context sạch) và **ghi rõ lý do** trong file review.
8. **GitHub approval:** nếu reviewer trùng tài khoản GitHub với tác giả, `gh pr review --approve` bị từ chối — file review trong repo là bằng chứng chính thức.

## Index

| File | PR | Branch | Verdict ban đầu | Verdict cuối | Trạng thái |
|---|---|---|---|---|---|
| `completed-pr-1-docs-vendor-strategy.md` | #1 | `chore/vc-dev-docs-vendor-strategy` | APPROVED WITH FOLLOW-UPS | APPROVED | MERGED |
| `completed-pr-2-migrate-plan.md` | #2 | `chore/migrate-plan` | HOLD (2 BLOCKER) | APPROVED | MERGED |
| `completed-pr-3-vc-dev-docs-review-workflow.md` | #3 | `chore/vc-dev-docs-review-workflow` | APPROVED WITH FOLLOW-UPS | APPROVED | MERGED |
| `completed-pr-4-coolify-cicd.md` | #4 | `feature/coolify-cicd` | CHANGES REQUIRED | APPROVED WITH FOLLOW-UPS | MERGED |
| `completed-pr-6-chore-migrate-to-vendored-repo-v2.md` | #6 | `chore/migrate-to-vendored-repo-v2` | APPROVED | APPROVED | MERGED |
| `completed-pr-10-prevent-site-id-redefinition.md` | #10 | `fix/undefined-site-id-startup-v2` | APPROVED | APPROVED | MERGED |
| `completed-pr-11-release-vc-prod.md` | #11 | `vc-dev` → `vc-prod` | APPROVED | APPROVED | MERGED |
| `pr-12-release-vc-prod-v2.md` | #12 | `vc-dev` → `vc-prod` | APPROVED | APPROVED | REVIEWED |
| `pr-13-docs-branch-protection.md` | #13 | `vc-dev` → `vc-prod` | APPROVED | APPROVED | REVIEWED |
| `pr-20-i18n-vietnamese.md` | #20 | `feature/i18n-vietnamese` | CHANGES REQUIRED | CHANGES REQUIRED | OPEN |

## Ghi chú lịch sử

- Review #1 và #2 chạy ngày 2026-10-21 bằng subagent `general` (Kilocode provider không có trong cấu hình OpenCode).
- Tại thời điểm review, thư mục local của landing còn tên cũ `..\landing`; **sau review** (2026-10-21) thư mục đã được đổi thành `..\vvveb-landing`. Các file review giữ nguyên nội dung lịch sử.
- `.gitignore` (`.gitignore:9` = `public/themes/*/*.html`) và `git check-ignore --no-index` là 2 phát hiện quan trọng nhất, đã đưa vào plan migration + instructions.
