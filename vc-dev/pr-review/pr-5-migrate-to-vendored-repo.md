# PR #5 — chore/migrate-to-vendored-repo

- **Reviewer:** subagent `general` (independent context, OpenCode)
- **Ngày:** 2026-10-21
- **Base:** `vc-dev` · **Commit review:** `250870aea3789eb1c125536724c4698fda216449`
- **Verdict ban đầu:** CHANGES REQUIRED
- **Verdict cuối:** CHANGES REQUIRED

## Findings

### 1. BLOCKER: `.gitignore` negation pattern sai cho `public/admin/default/index.html`
**Vị trí:** `.gitignore:14`  
**Vấn đề:** Pattern `!public/admin/themes/*/index.html` không khớp với `public/admin/default/index.html` (thiếu segment `themes/`). Kết quả: `git ls-files public/admin/default/index.html` trả về rỗng → file không được track.  
**Bằng chứng:**
```powershell
PS> git ls-files public/admin/default/index.html
(no output)
PS> ls public/admin/default/index.html
-a----  10/21/2026  11:13 AM  160255 index.html   # file tồn tại trên disk
PS> cat .gitignore | Select-String "admin.*index.html"
!public/admin/themes/*/index.html
```
**Fix:** Thêm `!public/admin/default/index.html` vào `.gitignore` (hoặc sửa pattern thành `!public/admin/**/index.html` nếu có nhiều theme admin).  
**Trạng thái:** OPEN

### 2. MAJOR: PR chứa thay đổi không liên quan (coolify production config)
**Vị trí:** Các file: `.dockerignore`, `Dockerfile.production`, `docker-compose.production.yaml`, `docker-entrypoint.sh`, `config/app.php`, `env.php`  
**Vấn đề:** PR #5 base là `vc-dev` (commit `e568a1d`) nhưng head `250870a` bao gồm commit `5aa1367` "chore(ci/coolify): thêm cấu hình deploy production" — hoàn toàn tách biệt với vendor migration.  
**Bằng chứng:**
```powershell
PS> git log --oneline e568a1d..250870a
250870a chore: vendor hóa 7 thành phần (Phương án D)
d642a3a fix(pr4): xóa fallback hardcode key/cronkey...
c399a86 docs(plan): cập nhật Handoff Summary...
5aa1367 chore(ci/coolify): thêm cấu hình deploy production   ← UNRELATED
e568a1d docs(vc-dev): dong bo ten thu muc vvveb-landing...
```
Diff `e568a1d..HEAD` chứa 6 file production config không liên quan đến migration.
**Fix:** Rebase branch `chore/migrate-to-vendored-repo` lên `vc-dev` **sau khi tách commit 5aa1367** ra branch/PR riêng, hoặc `git rebase -i` drop commit `5aa1367` khỏi branch này.  
**Trạng thái:** OPEN

### 3. MAJOR: `config/app.php` thay đổi secret handling (liên quan đến coolify commit)
**Vị trí:** `config/app.php:33-34`  
**Vấn đề:** Hardcoded `cronkey`/`key` thay bằng `getenv('CRON_KEY') ?: null` / `getenv('APP_KEY') ?: null`. Đây là một phần của coolify production config, không phải vendor migration. Cũng tạo rủi ro: nếu env var không set → `null` có thể phá vỡ logic phụ thuộc key.  
**Bằng chứng:** Xem diff `config/app.php` trong finding #2.  
**Fix:** Cùng fix với finding #2 — tách ra PR riêng.  
**Trạng thái:** OPEN

### 4. MINOR: `env.php` thay đổi default `SQL_CHECK`/`PAGE_CACHE` (liên quan coolify)
**Vị trí:** `env.php:25-32`  
**Vấn đề:** `SQL_CHECK` và `PAGE_CACHE` từ `true` → `false`. Là production hardening, không thuộc migration.  
**Fix:** Cùng fix với finding #2.  
**Trạng thái:** OPEN

### 5. NIT: Plan file `migrate-to-vendored-repo.md` trạng thái chưa cập nhật
**Vị trí:** `vc-dev/issues/migrate-to-vendored-repo.md:6`  
**Vấn đề:** `Trạng thái tổng: PLANNING` nhưng PR đã mở (T9 done). Nên là `IN_PROGRESS` hoặc `IN_REVIEW`.  
**Fix:** Cập nhật trạng thái trong plan file.  
**Trạng thái:** OPEN

### 6. NIT: `architecture.md` §2.1 table ghi "Trạng thái thư mục: ❌ rỗng" nhưng thực tế đã có nội dung
**Vị trí:** `vc-dev/instructions/architecture.md:86-92`  
**Vấn đề:** Bảng §2.1 mô tả trạng thái **trước migration** (rỗng), nhưng không có ghi chú rõ ràng rằng đây là trạng thái lịch sử. Người đọc sau có thể hiểu nhầm hiện tại vẫn rỗng.  
**Fix:** Thêm chú thích "(trước migration)" hoặc cập nhật cột "Trạng thái thư mục" thành "✅ đã vendor".  
**Trạng thái:** OPEN

---

## Claim đã xác minh đúng

| Claim | Lệnh kiểm tra | Kết quả chính |
|-------|--------------|---------------|
| `git submodule status` trống | `git submodule status` | (no output) ✅ |
| Không còn gitlink (mode 160000) | `git ls-files -s \| Select-String "^160000"` | (no output) ✅ |
| `.gitmodules` đã xóa | `Test-Path .gitmodules` | `False` ✅ |
| 7 path vendor có nội dung (>0 file) | `Get-ChildItem <7 paths> -Recurse -File \| Measure-Object` | Tất cả > 0 (landing: 2000+, admin: 1000+, blog: 200+, contact-form: 40+, payment: 20+, shipping: 20+, tinymce: 500+) ✅ |
| Không nested `.git`/`node_modules` trong index | `git ls-files \| Select-String "node_modules\|/\.git/"` | (no output) ✅ |
| `public/themes/landing/index.html` tracked | `git ls-files public/themes/landing/index.html` | `public/themes/landing/index.html` ✅ |
| `public/themes/blog-default/index.html` tracked | `git ls-files public/themes/blog-default/index.html` | `public/themes/blog-default/index.html` ✅ |
| Landing artifacts (sections/blocks/styles) tracked ở repo nguồn | `git -C ..\vvveb-landing ls-files --error-unmatch sections/sections.js blocks/blocks.js styles/styles.js` | 3 file đều có ✅ |
| Source commit `vvveb-landing` = `a801d3a` | `git -C ..\vvveb-landing rev-parse HEAD` | `a801d3ac808d9ae0a78858b87c6036381387fd14` ✅ |
| Source commit `vvveb-admin-template` = `2394323` | `git -C ..\vvveb-admin-template rev-parse HEAD` | `23943239ecef6dae8f3ef6918db477c6a671808b` ✅ |
| Source commit `vvveb-blog-default` = `e2eb5f7` | `git -C ..\vvveb-blog-default rev-parse HEAD` | `e2eb5f77157cec266f5e47c82ea3f36ac877df72` ✅ |
| `.gitignore` có negation `!public/themes/*/index.html` | `cat .gitignore \| Select-String "!public/themes.*index.html"` | `!public/themes/*/index.html` ✅ |
| `.gitignore` có rule loại `.git`/`node_modules` repo phụ | `cat .gitignore \| Select-String "vendor refresh"` | 14 dòng rule `.git` + `node_modules` ✅ |
| Không file PHP core nào bị sửa trong commit migration | `git diff 250870a^..250870a --name-only \| Select-String "\.php$" \| Where-Object { $_ -notmatch "^plugins/" -and $_ -notmatch "^public/themes/" -and $_ -notmatch "^public/admin/" }` | (no output) ✅ |
| Commit migration ghi commit nguồn cho 7 thành phần | `git log -1 --format=body 250870a` | Đầy đủ 7 commit SHA trong message ✅ |

---

## Claim không xác minh được

| Claim | Lý do |
|-------|-------|
| Temp clone commit `contact-form` = `0412f5d`, `payment` = `86742ac`, `shipping` = `6612fd9`, `tinymce-dist` = `3d62192` | Các repo này (Vvveb/contact-form, Vvveb/payment, Vvveb/shipping, tinymce/tinymce-dist) **không tồn tại local** (chỉ có fork của landing/admin/blog/VvvebJs). Không thể `git -C` kiểm tra. Architecture.md §2.5 ghi đúng các commit này, nhưng reviewer độc lập không thể verify bằng lệnh local. |
| Robocopy `/MIR /XD .git node_modules` đã chạy đúng | Không có log robocopy lưu lại; chỉ verify kết quả cuối (không có nested `.git`/`node_modules` trong index — đã kiểm tra ✅). |
| Smoke test thủ công (frontend, admin, editor, tinymce, contact-form) | Cần web server + DB + config/db.php — môi trường reviewer không có. Plan ghi T8 "COMPLETED" nhưng smoke test manual vẫn PENDING (xem plan T130-151). |

---

## Quyết định

**Verdict: CHANGES REQUIRED**

Migration vendor hóa (commit `250870a`) **kỹ thuật đúng đắn**: 7 thành phần đã vendor, `.gitmodules` xóa, không còn gitlink, index.html theme landing/blog tracked, artifacts landing có sẵn. Tuy nhiên **PR #5 không sạch** — nó kéo theo 3 commit không liên quan (`5aa1367` coolify production, `d642a3a` config/app.php/env.php, `c399a86` docs plan) làm nhiễu diff và rủi ro merge. Cần **rebase branch chỉ chứa commit migration** (có thể squash `c399a86` docs nếu liên quan) và tách production config ra PR riêng.

Ngoài ra, **bug `.gitignore` cho `public/admin/default/index.html`** (finding #1) phải fix trước khi merge — nếu không admin theme sẽ thiếu `index.html` và `Themes::getList()` hoặc logic render admin có thể thất bại âm thầm.

Các finding MINOR/NIT là documentation hygiene, không block merge nhưng nên fix cho nhất quán.