# Migration Phương án D — Vendor hóa, bỏ hoàn toàn git submodule

- **ID/Ticket:** issue #1 trong `vc-dev/issues/README.md` (bảng "Việc nền tảng đã biết")
- **Branch:** `chore/migrate-to-vendored-repo`
- **Owner hiện tại:** _chưa gán_ (`human | agent/model name`)
- **Trạng thái tổng:** `PLANNING`
- **Tạo ngày:** 2026-10-21 · **Cập nhật:** 2026-10-21

> ⚠️ **Đây là task ưu tiên #1 của dự án.** Hiện tại `git clone` repo xong **không chạy được hệ thống**: 7 thư mục (theme, admin template, plugin, tinymce) đều rỗng vì là gitlink chưa init. Xem `architecture.md` §2.1.
>
> **Lưu ý cho agent tiếp nhận:** đọc `## Handoff Summary` ở cuối file này **trước**, rồi mới đọc toàn bộ plan.

---

## Objective

Chuyển repo core từ mô hình git submodule sang mô hình **vendor hóa** (file thường, commit trực tiếp), đạt các tiêu chí đo lường được sau:

1. `git clone https://github.com/honggiang13/Vvveb.git && git checkout vc-dev` → **chạy được ngay**, không cần `--recurse-submodules`, không cần mạng tới repo ngoài.
2. `git ls-files -s | Select-String "^160000"` trả về **trống** (không còn gitlink nào).
3. `Test-Path .gitmodules` = **False**.
4. `(Get-ChildItem <7 path> | Measure-Object).Count` **> 0** cho từng path trong 7 thành phần.
5. Bảng theo dõi phiên bản vendor (`architecture.md` §2.5) được điền đủ **commit nguồn + ngày + thực hiện bởi** cho cả 7 dòng.
6. Smoke test đạt: frontend render (theme `landing` + `blog-default`), admin dashboard render, editor VvvebJs có **danh sách section/block/style**, WYSIWYG tinymce hoạt động, plugin `contact-form` activate/deactivate được.

## Scope & Impact Analysis

- **Module/file ảnh hưởng:**
  - 7 thư mục đích: `public/themes/landing`, `public/admin/default`, `public/themes/blog-default`, `plugins/contact-form`, `plugins/payment`, `plugins/shipping`, `public/js/libs/tinymce-dist`
  - `.gitmodules` (xóa)
  - `.gitignore` (thêm rule loại `node_modules`/`.git` của repo phụ + artifact build nếu chưa có)
  - `vc-dev/instructions/architecture.md` §2.5 (bảng theo dõi — **bắt buộc cập nhật**)
- **Mức can thiệp core (`architecture.md` §4.2):** **n/a (không sửa code PHP)**. Đây là thao tác ở tầng repo/git, không chạm logic ứng dụng. Không cần marker `vc-dev:patch`.
- **Giải pháp bảo vệ code core:** không áp dụng — không sửa file core nào. Điểm mấu chốt là **không** mang `.git/` và `node_modules/` của repo phụ vào core.
- **Rủi ro & giảm thiểu:**

  | Rủi ro | Mức | Giảm thiểu |
  |---|---|---|
  | Nested repo: mang `.git` của repo phụ vào core ⇒ git tạo submodule ẩn hoặc từ chối `git add` | Cao | **Bắt buộc** `robocopy /XD .git node_modules`; double-check bằng `git ls-files -s \| Select-String "^160000"` trước khi commit |
  | Vendor thiếu file build (`sections/sections.js`, `blocks/blocks.js`, `styles/styles.js`) ⇒ editor mất danh sách section/block **không báo lỗi** | Cao | Chạy `npm run gulp sections`, `blocks`, `styles` ở repo phụ **trước** khi mirror (T3) |
  | Dung lượng repo tăng vọt (`tinymce-dist` vài chục MB, −1 lịch sử nặng) | Trung | Chấp nhận. Ghi chú trong PR description; cân nhắc báo trước cho người clone lần đầu |
  | `robocopy /MIR` xóa nhầm file ngoài ý muốn (ví dụ ai đó có file local trong thư mục đích) | Trung | Luôn chạy `/L` (dry-run) trước; xác nhận danh sách xóa |
  | Mất khả năng cập nhật 1 dòng lệnh ⇒ vendor drift | Trung | Bảng theo dõi §2.5 là **bắt buộc**; quy trình refresh có kiểm soát (`architecture.md` §2.4) |
  | Repo phụ `..\blog-default` chưa tồn tại local | Thấp | Clone từ `honggiang13/blog-default` ở đúng gitlink `e2eb5f7` |
  | `.gitignore` hiện có thể đang ignore nhầm path vendor (ví dụ `*.js` trong theme) | Trung | Kiểm tra `git check-ignore -v <path>` cho từng đại diện trước khi `git add` |

- **Ảnh hưởng khi sync upstream:** **có, ở mức vừa.**
  - Upstream vẫn dùng submodule cho 7 thành phần này ⇒ mỗi lần merge từ `upstream/master`, git sẽ cố ghi lại gitlink và kéo `.gitmodules` trở về.
  - **Xử lý:** sau mỗi lần merge upstream, chạy lại bước xác minh (T1) và nếu upstream tái sinh `.gitmodules`/gitlink ⇒ quyết định **giữ vendor** (ưu tiên Phương án D) và ghi lại việc này trong commit merge. Ghi chú quy trình này vào `development.md` §2.4 khi phát sinh lần đầu.
  - Ghi rõ trong PR: đây là **divergence có chủ ý** với upstream ở tầng repo (không phải tầng code).
- **Ngoài phạm vi (Out of scope):**
  - Không nâng cấp/tùy biến thêm nội dung theme hay plugin trong PR này (chỉ vendor nguyên trạng đúng commit đã chốt).
  - Không sửa `public/js/vvvebjs` (đã là vendor copy + patch — xem `architecture.md` §2.7).
  - Không xử lý secret trong `config/app.php` (issue riêng #3).
  - Không thêm CI/Docker workflow dù mục tiêu D có lợi cho CI — làm ở issue riêng.
  - Không dọn `VvvebJs` (không nằm trong 7 submodule).

## Bảng quyết định commit nguồn (đã chốt với chủ dự án)

| Path | Repo nguồn | Commit cần vendor | Lý do |
|---|---|---|---|
| `public/themes/landing` | `honggiang13/vvveb-landing` | `a801d3a` | HEAD local mới hơn gitlink `431a650` — dùng bản mới nhất đã build |
| `public/admin/default` | `honggiang13/vvveb-admin-template` | `2394323` | HEAD local mới hơn gitlink `4cdf289` — dùng bản mới nhất đã build |
| `public/themes/blog-default` | `honggiang13/blog-default` | `e2eb5f7` | Fork `main` head bằng đúng gitlink — không lệch |
| `plugins/contact-form` | `Vvveb/contact-form` | `0412f5d` | Đúng gitlink superproject ghi |
| `plugins/payment` | `Vvveb/payment` | `86742ac` | Đúng gitlink superproject ghi |
| `plugins/shipping` | `Vvveb/shipping` | `6612fd9` | Đúng gitlink superproject ghi |
| `public/js/libs/tinymce-dist` | `tinymce/tinymce-dist` | `3d62192` | Đúng gitlink superproject ghi |

## Step-by-Step Tasklist

- [ ] PENDING: **T0** — Tạo Plan của PR migration (đang ở đây). Chuyển trạng thái tổng sang `IN_PROGRESS` + gán owner khi bắt đầu.
- [ ] PENDING: **T1** — Baseline & chuẩn bị: `git checkout vc-dev && git pull`; tạo branch `chore/migrate-to-vendored-repo` từ `vc-dev`; đảm bảo working tree sạch. Ghi lại **output baseline** của: `git submodule status`, `git ls-files -s | Select-String "^160000"`, `Test-Path .gitmodules` (để so sánh trước/sau).
- [ ] PENDING: **T2** — Kiểm tra `.gitignore` hiện tại: chạy `git check-ignore -v` cho một file đại diện ở mỗi trong 7 path (ví dụ `public/themes/landing/index.html`, `plugins/contact-form/plugin.php`). Nếu bị ignore nhầm ⇒ bổ sung rule `!` để un-ignore trước khi vendor. Thêm rule cho `.git`/`node_modules` của repo phụ (phòng khi robocopy sót).
- [ ] PENDING: **T3** — Chuẩn bị nguồn cho 7 thành phần:
  - [ ] T3a — landing (`..\vvveb-landing`): xác nhận HEAD = `a801d3a`; chạy `npm install`; `npm run gulp`; **`npm run gulp sections`**; **`npm run gulp blocks`**; **`npm run gulp styles`**.
  - [ ] T3b — admin template (`..\vvveb-admin-template`): xác nhận HEAD = `2394323`; `npm install`; `npm run gulp`.
  - [ ] T3c — blog-default: clone `honggiang13/blog-default`, checkout `e2eb5f7`; build nếu theme có `gulpfile.js` + sinh `sections`/`blocks`/`styles` (kiểm tra `gulpfile.js` trước).
  - [ ] T3d — 4 thành phần upstream còn thiếu: clone `--no-checkout` `Vvveb/contact-form`, `Vvveb/payment`, `Vvveb/shipping`, `tinymce/tinymce-dist`, rồi `checkout` đúng commit ở bảng trên.
  - [ ] T3e — Ghi lại commit thực tế của **từng** nguồn vào bảng §2.5 (cột "Commit đã vendor") **trước khi** mirror — tránh quên.
- [ ] PENDING: **T4** — Mirror vào core (dry-run trước, mirror thật sau):
  ```powershell
  # DRY RUN — chỉ liệt kê, KHÔNG xóa
  robocopy ..\vvveb-landing        public\themes\landing        /MIR /XD .git node_modules /L /NP
  robocopy ..\vvveb-admin-template public\admin\default         /MIR /XD .git node_modules /L /NP
  robocopy <blog-default-src>      public\themes\blog-default  /MIR /XD .git node_modules /L /NP
  robocopy <contact-form-src>      plugins\contact-form        /MIR /XD .git node_modules /L /NP
  robocopy <payment-src>           plugins\payment             /MIR /XD .git node_modules /L /NP
  robocopy <shipping-src>          plugins\shipping            /MIR /XD .git node_modules /L /NP
  robocopy <tinymce-dist-src>      public\js\libs\tinymce-dist /MIR /XD .git node_modules /L /NP
  ```
  Xem xét danh sách file sẽ bị xóa, xác nhận không có file quan trọng. Rồi chạy lại **không có `/L`**.
- [ ] PENDING: **T5** — Gỡ cơ chế submodule (làm đúng thứ tự, xem `architecture.md` §2.6):
  ```powershell
  git rm --cached .gitmodules
  git rm --cached public/themes/landing public/admin/default public/themes/blog-default `
                plugins/contact-form plugins/payment plugins/shipping public/js/libs/tinymce-dist
  Remove-Item .gitmodules
  # Xóa .git của repo phụ nếu robocopy sót (phải trống trước khi git add)
  Get-ChildItem public\themes\landing\.git, public\admin\default\.git, `
    public\themes\blog-default\.git, plugins\contact-form\.git, plugins\payment\.git, `
    plugins\shipping\.git, public\js\libs\tinymce-dist\.git -Force -Recurse -ErrorAction SilentlyContinue |
    Remove-Item -Recurse -Force
  ```
- [ ] PENDING: **T6** — Thêm lại dưới dạng file thường rồi **xác minh trước khi commit**:
  ```powershell
  git add public/themes/landing public/admin/default public/themes/blog-default `
          plugins/contact-form plugins/payment plugins/shipping public/js/libs/tinymce-dist
  # Xác minh (cả 3 phải đạt)
  git submodule status                                 # phải TRỐNG
  git ls-files -s | Select-String "^160000"            # phải TRỐNG
  Test-Path .gitmodules                                # phải FALSE
  # Xác minh không có nested repo / file node_modules lọt vào index
  git ls-files | Select-String "node_modules|/\.git/"
  ```
- [ ] PENDING: **T7** — Cập nhật bảng theo dõi phiên bản vendor (`architecture.md` §2.5): điền commit nguồn, ngày vendor (2026-10-21), "đã build? có/không", người thực hiện. Cập nhật `architecture.md` §6 (checklist môi trường) từ 🔄 sang ✅.
- [ ] PENDING: **T8** — Clear cache + smoke test (xem Test Plan). Ghi lại bằng chứng vào `## Test Plan` / PR description.
- [ ] PENDING: **T9** — Commit + push + mở PR vào `vc-dev`. Commit message phải ghi **commit nguồn** của từng thành phần.
- [ ] PENDING: **T10** — Sau khi PR merge: cập nhật `vc-dev/issues/README.md` item 1 → `MERGED`; điền `## Handoff Summary` cuối cùng.

## Test Plan

Repo **không có test framework/CI** (`development.md` §5) ⇒ toàn bộ verify là thủ công/CLI.

**Verify tự động (phải chạy, dán output vào PR):**
- [ ] PENDING: `git submodule status` → trống
- [ ] PENDING: `git ls-files -s | Select-String "^160000"` → trống
- [ ] PENDING: `Test-Path .gitmodules` → `False`
- [ ] PENDING: `(Get-ChildItem <path> -Force | Measure-Object).Count` → `> 0` cho cả 7 path
- [ ] PENDING: `(Get-ChildItem public\themes\landing -Recurse -Directory -Force | Where-Object Name -eq '.git' | Measure-Object).Count` → `0` (không có nested repo)
- [ ] PENDING: `Test-Path public\themes\landing\sections\sections.js`, `...\blocks\blocks.js`, `...\styles\styles.js` → `True` (điều kiện để editor có danh sách section/block)
- [ ] PENDING: `php -l` không áp dụng (không sửa PHP) — nhưng chạy `git diff --staged --stat` để xác nhận **không** có file `.php` core nào bị sửa

**Smoke test thủ công (cần `config/db.php` — nếu chưa có, chạy installer CLI trước):**
- [ ] PENDING: Manual — Frontend: trang chủ render theme `landing` không lỗi (bật `DEBUG=true` quan sát)
- [ ] PENDING: Manual — Frontend: site dùng theme `blog-default` render được (đổi tạm `config/sites.php` hoặc tạo site 2 rồi **revert**)
- [ ] PENDING: Manual — Admin: `/{admin-path}/` login OK, dashboard render (không trang trắng ⇒ `public/admin/default` đã có nội dung)
- [ ] PENDING: Manual — Editor: `/?module=/editor/editor` → panel hiển thị **danh sách section** và **danh sách block**, kéo thả được 1 section vào page
- [ ] PENDING: Manual — Editor: WYSIWYG tinymce hiện toolbar (⇒ `tinymce-dist` đã vendor đúng)
- [ ] PENDING: CLI — `php cli.php admin module=plugin/plugins action=checkPluginAndActivate plugin=contact-form` → success, không lỗi autoload
- [ ] PENDING: CLI — `php cli.php admin module=plugin/plugins action=deactivate plugin=contact-form` → success
- [ ] PENDING: Manual — Form contact render ở frontend và gửi được (nếu có SMTP/DB test)

**Kiểm tra độ bền (đặc thù Phương án D):**
- [ ] PENDING: Manual — `git stash list` / thử `git clone` repo vào thư mục tạm rồi `git checkout vc-dev` **không** dùng `--recurse-submodules` ⇒ theme/admin/plugin phải có nội dung ngay. Đây là test quan trọng nhất của cả migration.

## Handoff Summary

### Status Current Phase
- Phase: **Phase 0 — Planning** (viết plan, chưa thực thi)
- Đã xong: PR docs (#1) — `chore/vc-dev-docs-vendor-strategy` đã push, PR mở vào `vc-dev`.
- Còn lại: T1–T10 (PENDING toàn bộ — chưa ai bắt đầu)
- Commit cuối: `8bb675b` trên `chore/vc-dev-docs-vendor-strategy`
- Test đã chạy: chưa (đây là plan)

### Key Decisions & Findings
1. **Phương án D được chốt thay vì submodule-based (A/B) hoặc symlink (C).** Lý do: loại bỏ failure mode "clone xong không chạy được", CI/Docker không cần `--recurse-submodules`, và các repo phụ đã fork về `honggiang13` nên nằm trong tầm kiểm soát. Xem `architecture.md` §2.2.
2. **Vendor theo HEAD local mới nhất, không theo gitlink cũ.** landing `a801d3a` (mới hơn `431a650`) và admin `2394323` (mới hơn `4cdf289`) — chấp nhận lệch baseline upstream ở 2 thành phần UI để lấy bản đã build mới nhất. blog-default không lệch (`e2eb5f7`).
3. **Điều KHÔNG nên làm:** (a) không dùng `Copy-Item`/`xcopy` thay `robocopy /MIR` — sẽ để lại file mồ côi; (b) không quên `gulp sections|blocks|styles` trước khi vendor theme — editor mất section/block **không báo lỗi**; (c) không mang `.git`/`node_modules` của repo phụ vào core.
4. **Phát hiện quan trọng:** `admin/controller/editor/editor.php::loadThemeAssets()` glob `$themeFolder/{inputs,components,blocks,sections,styles}/*.js`. Các file này là **build artifact** của theme, sinh bởi `vvveb.js:419` và `vvveb.js:464` (`sections/sections.js`, `blocks/blocks.js`, `styles/styles.js`). Nhiều khả năng chúng bị `.gitignore` **trong repo theme** ⇒ phải build lại, không thể chỉ `git clone` là có.
5. **`public/js/vvvebjs` KHÔNG nằm trong phạm vi này** — đã là vendor copy có patch (khác hash upstream). Không copy đè.
6. **Divergence với upstream ở tầng repo là có chủ ý** ⇒ cần quy trình xử lý mỗi lần merge `upstream/master` (upstream sẽ tái sinh `.gitmodules`/gitlink).

### Context for Next Agent
- **File đã tạo/sửa:** `vc-dev/` (6 file docs, commit `8bb675b`); file plan này `vc-dev/issues/migrate-to-vendored-repo.md`.
- **File CHƯA nên sửa trong PR migration:** mọi file `.php` (không cần thiết), `public/js/vvvebjs` (vendor copy đã patch), `config/app.php` (secret — issue riêng).
- **Biến môi trường/cấu hình:** nếu chưa có `config/db.php` thì phải chạy installer trước khi smoke test admin/frontend.
- **Lệnh build/test cần chạy:** xem Test Plan phía trên. Đặc biệt `robocopy ... /L` phải chạy **trước** mirror thật.
- **Trạng thái môi trường/vendor (tại thời điểm viết plan):** `git submodule status` = 7 dòng có prefix `-`; 7 thư mục **đều rỗng (0 file)**; `.gitmodules` còn tồn tại (nguyên bản upstream, URL vẫn là `givanz/landing`); `git ls-files -s | Select-String "^160000"` = **7**. Repo phụ local: `..\landing` (HEAD `a801d3a`), `..\vvveb-admin-template` (HEAD `2394323`), `..\VvvebJs` (HEAD `1acbab7`). **`..\blog-default` CHƯA tồn tại local** — phải clone.
- **Cạm bẫy đã gặp:** chưa (chưa thực thi). Cạm bẫy dự kiến: `.gitmodules` URL của landing ở upstream vẫn ghi `givanz/landing` — **không cần** sửa URL vì file sẽ bị xóa hoàn toàn; đừng mất thời gian "re-point" nó.

### Next Action Items
1. **Ngay khi tiếp nhận:** đọc `architecture.md` §2.2–§2.6, rồi chuyển `**Trạng thái tổng:**` sang `IN_PROGRESS` và gán `**Owner hiện tại:**`.
2. Thực hiện T1 → T2 (baseline + kiểm tra `.gitignore`) trước khi clone/build bất cứ thứ gì.
3. T3 phụ thuộc mạng + Node.js: nếu máy chưa có `npm` hoặc không clone được `tinymce-dist` (dung lượng lớn) ⇒ đánh dấu `BLOCKED` với lý do cụ thể, không tự ý bỏ qua thành phần nào.
4. Nếu bị chặn ở T5 (`git rm --cached` từ chối vì nested repo) ⇒ xóa `.git` của repo phụ rồi thử lại; ghi lại vào mục "Cạm bẫy đã gặp".
