# PR #1 — `chore/vc-dev-docs-vendor-strategy`

- **Reviewer:** subagent `general` (context sạch, độc lập) — Kilocode không khả dụng trong cấu hình.
- **Ngày:** 2026-10-21
- **Diff review lần đầu:** commit `8bb675b` (6 file docs under `vc-dev/`)
- **Verdict ban đầu:** APPROVE với follow-up fixes (merge sau khi fix)
- **Verdict cuối:** ✅ **APPROVED + MERGED** (fix commit `d65805f`)

## Findings và trạng thái fix

### 1. MINOR — Số liệu sai: `vvvebjs` "34 file"
- **Vị trí:** `vc-dev/instructions/architecture.md` §(vvvebjs)
- **Thực tế:** 24 file cấp gốc, 157 file tracked tổng (`Get-ChildItem public\js\vvvebjs -File` = 24; `git ls-files public/js/vvvebjs | Measure-Object` = 157).
- **Fix:** ✅ Cập nhật thành "24 file ở cấp gốc … 157 file tính cả `components/` và `icons/`".
- **Trạng thái:** FIXED trong `d65805f`.

### 2. MINOR — Số liệu sai: "24 bộ icon"
- **Vị trí:** `vc-dev/instructions/architecture.md` (bảng §5) và `vc-dev/instructions/agent-guide.md`
- **Thực tế:** 30 thư mục dưới `public/resources/svg/icons`.
- **Fix:** ✅ Đổi thành "30 bộ icon".
- **Trạng thái:** FIXED trong `d65805f`.

### 3. MAJOR — Tên repo ≠ tên thư mục local không được phân biệt
- **Vị trí:** nhiều lệnh trong `architecture.md` §2.1/§2.4, `skills.md` §1.2/§4/§9, `development.md` §1.4
- **Thực tế:** remote `honggiang13/landing` đã đổi thành `honggiang13/vvveb-landing`, nhưng thư mục local vẫn là `..\landing` (đã verify: `Test-Path ..\vvveb-landing` = False, `Test-Path ..\landing` = True). Các lệnh dùng `..\vvveb-landing` sẽ fail.
- **Fix:** ✅ Thêm bảng phân biệt remote/thư mục local + 2 cách xử lý (rename hoặc clone mới) vào `architecture.md` §2.1; cảnh báo đầu `skills.md` §1.2 và `development.md` §4; sửa map `$Component` trong script vendor (`..\blog-default` → `..\vvveb-blog-default`).
- **Trạng thái:** FIXED trong `d65805f`.

### 4. MAJOR — Tên fork blog-default không canonical
- **Vị trí:** `architecture.md` §2.1, `skills.md` §10
- **Thực tế:** `gh repo view honggiang13/blog-default --json name` trả về `vvveb-blog-default` — tên canonical là `honggiang13/vvveb-blog-default`.
- **Fix:** ✅ Chuẩn hóa mọi chỗ thành `honggiang13/vvveb-blog-default` (giữ 1 chỗ cố ý nhắc tên cũ trong cảnh báo "dùng tên mới").
- **Trạng thái:** FIXED trong `d65805f`.

### 5. MAJOR — `.gitignore:9` `public/themes/*/*.html` nuốt HTML theme (bẫy khi vendor)
- **Vị trí:** mọi bước `git add` trong docs
- **Thực tế (verify):** `git check-ignore -v --no-index public/themes/landing/index.html` → `.gitignore:9:public/themes/*/*.html`. `Themes::getList()` (`system/extensions/themes.php:70`) glob `public/themes/*/index.html` ⇒ nếu HTML bị ignore âm thầm, theme không render.
- **Fix:** ✅ Thêm warning + bước un-ignore `!public/themes/*/*.html` + verify `git ls-files public/themes/landing/index.html` không rỗng vào `architecture.md`, `skills.md` (§1.2, §7.3, checklist) và `development.md`.
- **Trạng thái:** FIXED trong `d65805f`.

### 6. MAJOR — `git check-ignore` без `--no-index` trên path là submodule
- **Thực tế (verify):** `git check-ignore -v public/themes/landing/index.html` → `fatal: Pathspec ... is in submodule` (exit 128).
- **Fix:** ✅ Mọi lệnh `git check-ignore` trong docs thêm `--no-index`; thêm dòng troubleshooting tương ứng trong `skills.md` §7.3.
- **Trạng thái:** FIXED trong `d65805f`.

### 7. MAJOR — Claim "gulp sections|blocks|styles bắt buộc" quá mức
- **Thực tế (verify):** tại landing `a801d3a`, `sections/sections.js`, `blocks/blocks.js`, `styles/styles.js` **đều đã được track** (`git ls-files --error-unmatch` cả 3 OK, không bị ignore trong repo theme). Không phải theme nào cũng có đủ 3 nhóm (blog-default có thể thiếu `sections/`/`blocks/`).
- **Fix:** ✅ Đổi thành quy tắc có điều kiện: kiểm tra `git ls-files` trước; build lại khi file thiếu hoặc `src/` vừa sửa; không giả định mọi theme đủ 3 nhóm. Áp dụng cho `architecture.md` §2.4, `skills.md` §4.1/§9.4, `development.md` §1.4.
- **Trạng thái:** FIXED trong `d65805f`.

### 8. NIT — Idiom bảng §5 ("❌ sửa trực tiếp") dễ hiểu sai
- **Fix:** ✅ Đổi thành "❌ **không** sửa trực tiếp".
- **Trạng thái:** FIXED trong `d65805f`.

## Verified-accurate claims (xác minh đúng)
- Gitlink superproject: landing `431a650`, admin/default `4cdf289`, blog-default `e2eb5f7`, contact-form `0412f5d`, payment `86742ac`, shipping `6612fd9`, tinymce-dist `3d62192` — khớp `git ls-files -s | Select-String "^160000"` (7 dòng).
- HEAD local: landing `a801d3a`, admin `2394323` — mới hơn gitlink.
- `admin/controller/editor/editor.php::loadThemeAssets()` glob `$themeFolder/{inputs,components,blocks,sections,styles}/*.js` — đúng.
- `system/extensions/themes.php:70` glob `public/themes/*/index.html` — đúng.
- `config/sites.php` trỏ theme mặc định site 1 = `landing` — đúng.
- `config/plugins.php` khai báo `contact-form` active cho site 1 — đúng.
- Không có test framework/CI — đúng (không tính là defect).

## Unverifiable claims
- URL GitHub redirect `honggiang13/landing` → `vvveb-landing`: xác minh gián tiếp qua `gh repo view` (trả tên mới), không test HTTP redirect trực tiếp.

## Quyết định
✅ **APPROVED** sau khi toàn bộ findings được fix trong commit `d65805f` và tự xác minh lại bằng lệnh thực tế. Merge vào `vc-dev`.
