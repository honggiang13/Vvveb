# PR #2 — `chore/migrate-plan`

- **Reviewer:** subagent `general` (context sạch, độc lập) — Kilocode không khả dụng trong cấu hình.
- **Ngày:** 2026-10-21
- **Diff review lần đầu:** commit `0eff7b4` (thêm `vc-dev/issues/migrate-to-vendored-repo.md`, sửa `vc-dev/issues/README.md`)
- **Verdict ban đầu:** ⛔ **HOLD (2 BLOCKER)** — không được merge cho đến khi fix
- **Verdict cuối:** ✅ **APPROVED + MERGED** (fix commit `ffdd6d2`, sau khi merge docs fixes từ PR #1)

## Findings và trạng thái fix

### 1. BLOCKER — Bẫy `.gitignore`: `public/themes/*/*.html` làm mất HTML theme
- **Vị trí:** `migrate-to-vendored-repo.md` — các bước T4→T6 (mirror rồi `git add`)
- **Vấn đề:** plan đi thẳng từ mirror sang `git add` mà không đụng `.gitignore`. Thực tế `.gitignore:9` = `public/themes/*/*.html` (ngoại lệ `*/src/*.html`) — file `index.html` cấp gốc của theme **bị ignore**, `git add` sẽ **âm thầm bỏ qua**. Hệ quả: `Themes::getList()` (`themes.php:70` glob `public/themes/*/index.html`) không phát hiện theme → site không render, mà plan vẫn "verify đạt".
- **Verify:** `git check-ignore -v --no-index public/themes/landing/index.html` → `.gitignore:9:public/themes/*/*.html`.
- **Fix:** ✅ (`ffdd6d2`)
  - Thêm row rủi ro **Cao** trong bảng §3 + row artifact tương ứng.
  - T2: bắt buộc thêm `!public/themes/*/*.html` (và `!public/admin/themes/*/*.html`) **trước** `git add`.
  - T6 + Test Plan: thêm verify `git ls-files public/themes/{landing,blog-default}/index.html` **không rỗng**.
  - Handoff: thêm "Điều KHÔNG nên làm (d)".

### 2. BLOCKER — Lệnh `git check-ignore` thiếu `--no-index` (exit 128)
- **Vị trí:** T2 (bản gốc: `git check-ignore -v <path>`)
- **Thực tế (verify):** khi path vẫn còn là submodule, `git check-ignore -v public/themes/landing/index.html` → `fatal: Pathspec ... is in submodule` (exit 128) — agent copy-paste sẽ fail ngay bước đầu.
- **Fix:** ✅ (`ffdd6d2`) Mọi lệnh dùng `--no-index`; ghi rõ lý do + exit 128 trong T2 và bảng rủi ro.

### 3. MAJOR — Sai tên repo blog-default
- **Vị trí:** bảng nguồn (§4), T3c, dòng rủi ro, Handoff
- **Thực tế (verify):** `gh repo view honggiang13/blog-default --json name` → `vvveb-blog-default`. Bản gốc còn ghi `Clone từ honggiang13/blog-default` và dùng `<blog-default-src>` placeholder.
- **Fix:** ✅ (`ffdd6d2`) Chuẩn hóa `honggiang13/vvveb-blog-default` (URL clone đầy đủ `https://github.com/honggiang13/vvveb-blog-default.git`).

### 4. MAJOR — Lộn xộn tên thư mục local: `..\landing` vs `..\vvveb-landing`, `..\blog-default` chưa tồn tại
- **Vị trí:** T3, T4 (lệnh robocopy), dòng môi trường trong Handoff
- **Thực tế (verify):** `Test-Path ..\vvveb-landing` = **False**, `Test-Path ..\landing` = **True**. Bản gốc viết `robocopy ..\vvveb-landing ...` ⇒ fail. `..\blog-default` chưa tồn tại.
- **Fix:** ✅ (`ffdd6d2`)
  - Cảnh báo T3: phân biệt tên repo/thư mục local + 2 cách xử lý (rename `Rename-Item ..\landing vvveb-landing` hoặc dùng tên cũ), link `architecture.md` §2.1.
  - T4: `robocopy ..\landing ...` (khớp thực tế) + hướng dẫn clone blog-default về `..\vvveb-blog-default`.
  - Handoff: cập nhật trạng thái `..\landing` là "tên thư mục cũ".

### 5. MAJOR — Claim "artifact bị ignore trong repo theme → bắt buộc build" sai với landing
- **Vị trí:** Handoff Key Decisions #4 (bản gốc: "Nhiều khả năng chúng bị `.gitignore` **trong repo theme** ⇒ phải build lại")
- **Thực tế (verify):** `git -C ..\landing ls-files --error-unmatch sections/sections.js blocks/blocks.js styles/styles.js` → cả 3 **đều track**; `check-ignore` → không bị ignore (exit 1).
- **Fix:** ✅ (`ffdd6d2`) Đổi thành: kiểm tra `git ls-files` trước; landing `a801d3a` đã track sẵn; không phải theme nào cũng đủ 3 nhóm; build lại **chỉ khi** thiếu hoặc `src/` vừa sửa. T3a/T3c, §3 rủi ro, Handoff #3/#4 đều cập nhật.

### 6. MINOR — Verify "cả 3 phải đạt" trong T6 thiếu kiểm tra HTML
- **Fix:** ✅ (`ffdd6d2`) Thêm 3 lệnh `git ls-files .../index.html` vào block verify T6 và 1 checklist Test Plan mới.

## Verified-accurate claims (xác minh đúng)
- 7 gitlink + giá trị commit khớp `git ls-files -s` (mảng §4 đúng từng hash).
- 7 thư mục vendor đang rỗng, `.gitmodules` còn nguyên — đúng trạng thái "chưa migrate" (không phải defect).
- Thứ tự T5 (`git rm --cached .gitmodules` + 7 path → `Remove-Item .gitmodules` → verify) là đúng; `robocopy /MIR /XD .git node_modules` là cách mirror đúng ngữ nghĩa; `/L` dry-run trước là đúng.
- `loadThemeAssets()` glob khớp code thật (xem pr-1).
- Lệnh smoke test CLI (`php cli.php admin module=plugin/plugins action=...`) đúng pattern của repo.
- Plan không thực thi `git rm --cached` sai thứ tự, không có bước xóa file user work.

## Unverifiable claims
- "tinymce-dist vài chước MB" — ước lượng, chưa clone về để đo (không ảnh hưởng an toàn).
- Kết quả smoke test — plan chưa chạy (đúng, vì đây là plan).

## Quyết định
⛔→✅ **HOLD → APPROVED.** 2 BLOCKER (bẫy `.gitignore` HTML, `check-ignore` thiếu `--no-index`) đều đã được **verify thực tế** trước khi fix, cùng 2 MAJOR tên/thư mục và 1 MAJOR sai lệch claim artifact. Toàn bộ fix trong `ffdd6d2` (plus docs fixes merge từ PR #1). Merge vào `vc-dev`.
