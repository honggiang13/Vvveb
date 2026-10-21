# vc-dev — Developer Workspace & Governance

Thư mục `vc-dev/` là **single source of truth** cho quy trình phát triển, tài liệu kiến trúc, tracking issue/feature, và bằng chứng review PR. Không chứa code chạy production — chỉ chứa **tài liệu điều phối (governance docs)**.

## Cấu trúc thư mục

```
vc-dev/
├── README.md                    # File này — tổng quan & hướng dẫn bootstrap
├── instructions/                # Tài liệu hướng dẫn BẮT BUỘC tuân thủ (thừa kế nguyên vẹn)
│   ├── architecture.md          # Kiến trúc hệ thống, chiến lược vendor, extension points
│   ├── development.md           # Workflow phát triển, PR & Review, checklist, docs update
│   ├── agent-guide.md           # Quy ước & ràng buộc cho AI Agents (contract)
│   └── skills.md                # Lệnh, script, build tools, automation cheat-sheet
├── features/                    # Plan & Tasklist cho tính năng mới
│   ├── README.md                # Index + quy tắc (done = rename completed-<slug>.md)
│   ├── vc-import-csv.md         # Plan: port plugin VCenter Import CSV
│   └── vc-theme-vcenter.md      # Plan: theme mới VCenter từ UI/UX tenant
├── issues/                      # Plan & Tasklist cho bug fix / nợ kỹ thuật / migration
│   ├── README.md                # Index + danh sách việc nền tảng
│   └── migrate-to-vendored-repo.md # Migration Phương án D (ĐÃ MERGED)
├── pr-review/                   # Bằng chứng review PR độc lập (BẮT BUỘC trước merge)
│   ├── README.md                # 8 quy tắc + index table
│   ├── pr-1-docs-vendor-strategy.md
│   ├── pr-2-migrate-plan.md
│   ├── pr-3-vc-dev-docs-review-workflow.md
│   ├── pr-4-coolify-cicd.md
│   ├── pr-5-migrate-to-vendored-repo.md
│   └── pr-6-chore-migrate-to-vendored-repo-v2.md
└── wip/                         # Work In Progress — NƠI DUY NHẤT ĐƯỢC SINH FILE PHÁT SINH
    └── README.md                # Mục đích folder
```

## Ý nghĩa từng thư mục/file

| Path | Vai trò | Ai ghi? | Khi nào? |
|------|---------|---------|----------|
| `instructions/*.md` | **Contract** — quy tắc cứng, workflow, kiến trúc | Human/Lead dev | Setup dự án; cập nhật khi thay đổi quy trình |
| `features/*.md` | Plan tính năng mới (Objective, Scope, Tasklist, Test Plan, Handoff) | Agent/Dev | **Trước khi code** tính năng mới |
| `issues/*.md` | Plan bug fix / migration / nợ kỹ thuật | Agent/Dev | **Trước khi fix** bug hoặc migration |
| `pr-review/*.md` | Kết quả review độc lập (verdict, findings, claim verification) | **Reviewer độc lập** | **Bắt buộc** sau khi PR mở, trước khi merge |
| `wip/*` | File tạm: script test, DB sqlite, HTML test, build output, log, temp | Agent/Dev | Trong quá trình làm task; **không commit** trừ `README.md` |

## Quy tắc vàng (non-negotiable)

1. **Không code trước khi có Plan** — mọi task bắt đầu bằng file trong `features/` hoặc `issues/`
2. **Review độc lập bắt buộc** — PR không có file `pr-review/pr-<n>-<slug>.md` **không được merge**
3. **Reviewer ≠ Author** — người review phải là session/agent/context hoàn toàn khác người viết PR
4. **File phát sinh chỉ ở `wip/`** — tuyệt đối không sinh `.py`, `.sqlite`, `.html` test, build artifact ở root, `source/`, `public/`
5. **Handoff Summary bắt buộc** — mỗi phase kết thúc phải có `## Handoff Summary` trong plan file

---

## Hướng dẫn Bootstrap cho Dự án Mới (Agent tự thực hiện)

Khi nhận một repo mới **chưa có `vc-dev/`**, agent hãy thực hiện tuần tự:

### 1. Tạo cấu trúc thư mục

```powershell
# Từ repo root
New-Item -ItemType Directory -Force `
  vc-dev, `
  vc-dev/features, `
  vc-dev/issues, `
  vc-dev/pr-review, `
  vc-dev/wip, `
  vc-dev/instructions
```

### 2. Copy file instruction core (THỪA KẾ NGUYÊN VẸN)

Các file sau **copy nguyên vẹn** từ template/reference repo (không sửa nội dung trừ khi có lý do cụ thể ghi trong PR):

| File | Lý do thừa kế nguyên vẹn |
|------|------------------------|
| `instructions/development.md` | Workflow chuẩn, PR template, review process — áp dụng cho mọi project Vvveb-like |
| `instructions/agent-guide.md` | Quy tắc agent (DO/DON'T, workflow 8 bước, handoff format) — contract cứng |
| `instructions/skills.md` | Lệnh git, PHP, Docker, build, vendor refresh, review evidence — tooling shared |
| `vc-dev/wip/README.md` | Quy tắc folder wip — giữ nguyên |
| `vc-dev/pr-review/README.md` | 8 quy tắc review + index template — giữ nguyên |
| `vc-dev/features/README.md` | Index + quy tắc `completed-<slug>.md` — giữ nguyên |
| `vc-dev/issues/README.md` | Index + template — giữ nguyên |

> **Lưu ý:** Chỉ sửa các file này khi có **decision record** trong PR (ví dụ: thay đổi workflow, thêm quy tắc mới). Mọi sửa đổi phải qua review độc lập.

### 3. Tạo/Phân tích lại file thiết kế (CẦN RÀ SOÁT, VIẾT LẠI)

File **architecture.md** chứa kiến trúc cụ thể của dự án hiện tại — **KHÔNG copy nguyên vẹn**. Agent phải:

```markdown
## Quy trình xử lý architecture.md cho project mới:

1. Đọc architecture.md hiện tại để hiểu pattern
2. Phân tích source code dự án mới:
   - Cấu trúc thư mục (public/, system/, admin/, plugins/)
   - Extension points: Event system, Plugin system, Theme engine, vtpl
   - Database schema, config system, routing
   - Vendor dependencies (theme, admin template, JS libs)
3. Viết lại architecture.md §1-§3 (Tổng quan, Multi-repo, Extension points)
   - Giữ nguyên format/section nhưng nội dung phản ánh thực tế project mới
4. §4 (Source code management) — giữ nguyên workflow git/PR
5. §2 (Multi-Repo Assessment) — cập nhật theo vendor dependencies thực tế
6. §2.5 Vendor tracking table — reset về trạng thái "chờ vendor" hoặc điền theo thực tế
```

**Checklist phân tích source code mới (chạy trước khi viết architecture.md):**

```powershell
# 1. Kiểm tra extension points
grep -rn "Event::on\|Event::trigger" --include=*.php system/
grep -rn "Plugin::" --include=*.php system/
grep -rn "Theme::" --include=*.php system/

# 2. Kiểm tra theme/admin vendor hiện tại
ls public/themes/
ls public/admin/
ls public/js/libs/

# 3. Kiểm tra submodule/gitlink
git submodule status
git ls-files -s | Select-String "^160000"

# 4. Kiểm tra config system
ls config/
cat config/app.php  # xem key/cronkey handling
cat config/db.php 2>$null || echo "chưa có db.php"

# 5. Kiểm tra builder JS
ls public/js/vvvebjs/
```

### 4. Tạo file index rỗng

```powershell
# features/README.md — copy từ template, chỉ giữ header + bảng trống
# issues/README.md — copy từ template, chỉ giữ header + bảng trống
# pr-review/README.md — copy từ template (8 quy tắc + index table trống)
```

### 5. Commit bootstrap

```powershell
git add vc-dev/
git commit -m "chore(vc-dev): bootstrap governance workspace for new project

- Copy core instructions (development, agent-guide, skills) nguyên vẹn
- architecture.md: rewritten based on new project source analysis
- features/issues/pr-review/wip: initialized with templates
- wip/README.md: folder rule documented
"
```

---

## Checklist Agent khi nhận project mới

- [ ] Đã chạy bootstrap tạo `vc-dev/` đầy đủ?
- [ ] `instructions/development.md`, `agent-guide.md`, `skills.md` copy nguyên vẹn?
- [ ] `architecture.md` đã **phân tích source code mới** và viết lại §1-§3, §2?
- [ ] `architecture.md` §2.5 vendor table reset/điền đúng thực tế?
- [ ] `features/README.md`, `issues/README.md`, `pr-review/README.md` có index template?
- [ ] `wip/README.md` tồn tại?
- [ ] Không có file rác ở root/source/public?
- [ ] Đã commit bootstrap với message rõ ràng?

---

## Maintenance (Duy trì vc-dev/)

| Sự kiện | Hành động | File cập nhật |
|---------|-----------|---------------|
| Thêm feature mới | Tạo `features/<slug>.md` + cập nhật `features/README.md` table | features/ |
| Bug fix / migration | Tạo `issues/<slug>.md` + cập nhật `issues/README.md` table | issues/ |
| Mở PR | Reviewer tạo `pr-review/pr-<n>-<slug>.md` + cập nhật `pr-review/README.md` | pr-review/ |
| Vendor refresh | Cập nhật `architecture.md` §2.5 (commit nguồn, ngày, build status) | architecture.md |
| Thay đổi workflow | Cập nhật `development.md` + `agent-guide.md` + review độc lập | instructions/ |
| Thêm lệnh/script mới | Cập nhật `skills.md` | skills.md |

---

## Reference: Template Plan (copy cho feature/issue mới)

```markdown
# <Tên feature/issue>
- **ID/Ticket:** ... · **Branch:** `feature/<name>` hoặc `chore/<name>` · **Owner hiện tại:** ...
- **Trạng thái tổng:** PLANNING | IN_PROGRESS | REVIEW | MERGED | BLOCKED
- **Tạo ngày:** YYYY-MM-DD · **Cập nhật:** YYYY-MM-DD

## Objective
<1-3 dòng mục tiêu đo lường được>

## Scope & Impact Analysis
- **Module/file ảnh hưởng:** ...
- **Mức can thiệp core (architecture.md §4.2):** mức 1-6 — lý do chọn
- **Giải pháp bảo vệ code core:** ...
- **Rủi ro & giảm thiểu:** ...

## Step-by-Step Tasklist
- [ ] PENDING: T1 — ...
- [ ] PENDING: T2 — ...

## Test Plan
- [ ] PENDING: Manual — ...
- [ ] PENDING: CLI/automation — ...

## Handoff Summary
### Status Current Phase
### Key Decisions & Findings
### Context for Next Agent
### Next Action Items
```

---

## Lệnh nhanh (PowerShell)

```powershell
# Kiểm tra trạng thái vc-dev
ls vc-dev/ -Recurse -Directory
ls vc-dev/ -File | Select-Object Name, Length

# Verify không file rác ngoài wip
git status --short | Where-Object { $_ -notmatch '^.\s+vc-dev/wip/' }

# Tạo plan mới nhanh
$slug = "my-feature"
cp vc-dev/features/README.md vc-dev/features/$slug.md  # sau đó edit nội dung
```

---

*File này là phần của governance workspace. Mọi thay đổi cấu trúc `vc-dev/` phải qua review độc lập theo `development.md` §6.3.*