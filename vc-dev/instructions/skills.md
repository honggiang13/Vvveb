# Skills — Lệnh, script, build tools & automation

> Tra cứu nhanh mọi command-line hữu ích cho dự án Vvveb fork.
> Tất cả lệnh chạy từ **repo root**: `E:\Personal\VCenter\vcenter-vvveb\Vvveb`.
> Shell ví dụ theo PowerShell (Windows); lệnh bash ghi rõ khi khác biệt.
> Khi sửa Windows, `php -r "array_map('unlink', glob('...'));"` thay cho `rm -rf`.

---

## 0. Bảng tra nhanh (cheat sheet)

| Việc cần làm | Lệnh |
|---|---|
| Kiểm tra vị trí & nhánh | `git branch --show-current; git status -s` |
| Kiểm tra thư mục vendor có nội dung | `(Get-ChildItem public\themes\landing -Force \| Measure-Object).Count` |
| Xác minh đã bỏ submodule | `git ls-files -s \| Select-String "^160000"` (phải trống) |
| Xóa cache compiled template | `php -r "array_map('unlink', glob('storage/compiled-templates/*'));"` |
| Xóa cache hệ thống | `php -r "array_map('unlink', glob('storage/cache/*'));"` |
| Xóa page cache | `php -r "array_map('unlink', glob('public/page-cache/*'));"` |
| Clear toàn bộ cache | `.\vc-dev\scripts\clear-cache.ps1` (nếu chưa có: xem §9.1) |
| Kiểm tra syntax 1 file | `php -l <file.php>` |
| Kiểm tra syntax toàn repo | `.\vc-dev\scripts\lint.ps1` (xem §9.2) |
| Chạy dev server | `php -S 127.0.0.1:8080 -t public` |
| Install qua CLI | `php cli.php install engine=sqlite admin[email]=a@b.c admin[username]=admin admin[password]=admin` |
| Activate plugin | `php cli.php admin module=plugin/plugins action=checkPluginAndActivate plugin=<slug>` |
| Deactivate plugin | `php cli.php admin module=plugin/plugins action=deactivate plugin=<slug>` |
| Chạy cron | `php cli.php app module=cron` |
| Test render 1 URL | `php cli.php app request_uri=/hello-world` |
| Tìm core patch đã chèn | `grep -rn "vc-dev:patch" --include=*.php .` |
| Tìm extension point | `grep -rn "Event::\(on\|trigger\)(" --include=*.php .` |
| **Vendor refresh theme** | Xem §1.2 — build ở repo phụ rồi `robocopy /MIR` vào core |
| Commit nguồn của repo phụ | `git -C ..\vvveb-landing rev-parse HEAD` |
| **Review PR (bắt buộc trước merge)** | Xem §9.5 — ghi `vc-dev/pr-review/pr-<n>-<slug>.md` |

---

## 1. Git

### 1.1 Trạng thái & định vị (chạy đầu tiên mỗi phiên)

```powershell
git branch --show-current
git status -s
git log --oneline -5
(Get-ChildItem public\themes\landing -Force | Measure-Object).Count   # 0 = thư mục vendor còn rỗng
Test-Path config/db.php                                               # False = chưa install
Test-Path .gitmodules                                                 # False = submodule đã được bỏ
```

### 1.2 Vendor (Phương án D — KHÔNG còn submodule)

Dự án **không dùng git submodule**. Toàn bộ theme/admin template/plugin vendor là file thường trong repo.
Chi tiết đầy đủ: `architecture.md` §2.2–§2.7. Tóm tắt lệnh:

> ℹ️ **Tên repo = tên thư mục local (đã đồng bộ 2026-10-21).** Các lệnh dưới dùng `..\vvveb-landing`, khớp với thư mục local hiện tại (đã đổi tên từ `..\landing`). Nếu máy khác còn tên cũ, xem `architecture.md` §2.1.

```powershell
# Xác minh đã bỏ submodule hoàn toàn
git submodule status                                  # phải trống
git ls-files -s | Select-String "^160000"             # phải trống (160000 = gitlink)
Test-Path .gitmodules                                 # phải False

# Xem commit nguồn hiện tại của repo phụ (đối chiếu bảng architecture.md §2.5)
git -C ..\vvveb-landing rev-parse HEAD
git -C ..\vvveb-admin-template rev-parse HEAD

# Vendor refresh: build ở REPO PHỤ (không phải trong core)
cd ..\vvveb-landing
npm install
npm run gulp
# Chỉ chạy 3 lệnh sau khi sections/blocks/styles KHÔNG có trong commit (kiểm tra trước):
#   git ls-files --error-unmatch sections/sections.js blocks/blocks.js styles/styles.js
npm run gulp sections; npm run gulp blocks; npm run gulp styles
cd ..\vvveb-admin-template; npm install; npm run gulp
cd ..\Vvveb

# Mirror vào core — /MIR để xóa file không còn tồn tại ở nguồn,
# /XD để không mang .git và node_modules của repo phụ vào core
robocopy ..\vvveb-landing        public\themes\landing        /MIR /XD .git node_modules
robocopy ..\vvveb-admin-template public\admin\default         /MIR /XD .git node_modules
robocopy ..\vvveb-blog-default   public\themes\blog-default  /MIR /XD .git node_modules

# Sau đó: clear cache → smoke test → cập nhật bảng §2.5 → commit
```

⚠️ **Kiểm tra `.gitignore` của core trước khi `git add`.** `.gitignore:9` là `public/themes/*/*.html` ⇒ HTML cấp gốc của theme (`index.html`, `about.html`, …) **bị ignore**. Nếu mirror xong mà không un-ignore, `git add` sẽ **âm thầm bỏ qua** các file này và site vẫn không render (vì `system/extensions/themes.php:70` glob `public/themes/*/index.html` để phát hiện theme). Xem §7.3.

Cờ `robocopy` hay dùng: `/MIR` mirror, `/XD <dir>` loại trừ thư mục, `/XF <file>` loại trừ file, `/L` chỉ liệt kê (dry-run — **nên chạy trước**), `/NP` bỏ thanh tiến trình, `/NFL /NDL` giảm log.

⚠️ Trước khi mirror lần đầu, chạy `robocopy ... /L` để xem trước danh sách file sẽ bị xóa.

### 1.3 Branch & sync upstream

```powershell
# Branch mới
git checkout vc-dev; git pull; git checkout -b feature/<slug>

# Sync upstream (chỉ trên master baseline)
git remote add upstream https://github.com/givanz/Vvveb.git   # 1 lần
git fetch upstream --tags
git checkout master; git merge --ff-only upstream/master; git push origin master
git checkout vc-dev; git merge master

# Xem diff giữa baseline và branch tích hợp
git log master..vc-dev --oneline
git diff master vc-dev --stat
```

### 1.4 Diff & kiểm tra trước commit

```powershell
git status -s
git diff --staged --stat
git ls-files -s | Select-String "^160000"   # phải trống — không được còn gitlink
git check-ignore -v <path>                  # vì sao file bị ignore
```

---

## 2. PHP

### 2.1 Syntax check

```powershell
php -l index.php
# Toàn bộ file .php của repo (trừ vendor/storage)
Get-ChildItem -Recurse -Filter *.php -File |
  Where-Object { $_.FullName -notmatch '\\(vendor|storage|node_modules)\\' } |
  ForEach-Object { php -l $_.FullName } | Select-String -NotMatch 'No syntax errors'
```

### 2.2 Dev server

```powershell
php -S 127.0.0.1:8080 -t public
# https://localhost:8080/install/   ← lần đầu
# http://localhost:8080/            ← frontend
# http://localhost:8080/admin/      ← admin
```

### 2.3 Cache maintenance

```powershell
# Compiled vtpl templates (sau khi sửa .tpl / HTML theme/admin)
php -r "array_map('unlink', glob('storage/compiled-templates/*'));"

# Cache driver (file cache)
php -r "array_map('unlink', glob('storage/cache/*'));"

# Page cache
php -r "array_map('unlink', glob('public/page-cache/*'));"

# Model SQL đã compile (sau khi sửa .sql)
php -r "array_map('unlink', glob('storage/model/*/*.php'));"
```

Bash tương đương:

```bash
rm -rf storage/compiled-templates/* storage/cache/* public/page-cache/* storage/model/*/*.php
```

### 2.4 Kiểm tra environment

```powershell
php -v
php -m | Select-String -Pattern 'mysqli|pdo|sqlite|pgsql|xml|zip|dom|curl|gettext|gd|mbstring'
php -r "echo ini_get('upload_max_filesize'), PHP_EOL, ini_get('post_max_size'), PHP_EOL;"
```

### 2.5 Ghi chú đặc thù

- `index.php:28` khai báo `V_VERSION = '0.0.7'`; `cli.php:71` còn `'0.0.1'` — **inconsistency có sẵn của upstream**, không phải bug do ta gây ra; đừng "sửa" mà không có task.
- `SQL_CHECK = true` trong `env.php` ⇒ sửa `.sql` sẽ tự recompile model sang `storage/model/{APP}/*.{engine}.php`.
- `PAGE_CACHE = true` ⇒ frontend có thể trả bản cache; khi test nhớ xóa `public/page-cache/*`.

---

## 3. CLI ứng dụng (`cli.php`)

### 3.1 Cú pháp

```bash
php cli.php [app] [name=value ...]
```

`app` ∈ `admin` | `app` | `install` (mặc định là `app`). Mọi cặp `name=value` trở thành tham số GET/POST khi gọi controller. Với `app=admin`, hệ thống dùng **super admin**.

### 3.2 Install (không cần browser)

```bash
# MySQL
php cli.php install engine=mysqli host=127.0.0.1 user=root password= database=vvveb \
  admin[email]=admin@example.com admin[username]=admin admin[password]=admin

# PostgreSQL
php cli.php install engine=pgsql host=127.0.0.1 user=postgres password= database=vvveb \
  admin[email]=admin@example.com admin[username]=admin admin[password]=admin

# SQLite (nhanh nhất cho dev)
php cli.php install engine=sqlite \
  admin[email]=admin@example.com admin[username]=admin admin[password]=admin
```

### 3.3 Vận hành plugin

```bash
# Kích hoạt (chạy setup lần đầu) & kiểm tra
php cli.php admin module=plugin/plugins action=checkPluginAndActivate plugin=contact-form

# Tắt
php cli.php admin module=plugin/plugins action=deactivate plugin=contact-form

# Import markdown vào site cụ thể
php cli.php admin module=plugins/markdown-import/settings action=import site_id=5 settings[path]=/docs/user
```

### 3.4 Nội dung & cron

```bash
# Xóa 1 post
php cli.php admin module=content/posts action=delete post_id=1

# Cron (dọn cache, backup theo lịch…)
php cli.php app module=cron

# Simulate 1 request frontend, in JSON
php cli.php app request_uri=/hello-world

# Import dữ liệu (pages/menus/plugins/themes)
php cli.php admin module=tools/import action=upload file[]=pages.xml file[]=menus.xml
```

### 3.5 Truy vấn controller admin tùy ý

```bash
php cli.php admin module=<module> action=<action> param=value param2=value2
# ví dụ: module=settings/general action=index site_id=1
```

---

## 4. Build assets (Node/gulp)

> ⚠️ **Build luôn chạy ở REPO PHỤ, không chạy trong core.** Sau khi build xong mới vendor sang core (`architecture.md` §2.4). Core không có `package.json`/`gulpfile.js` ở root. 
>
> ℹ️ **Tên repo = tên thư mục local (đã đồng bộ 2026-10-21).** Thư mục landing hiện là `..\vvveb-landing`. Xem `architecture.md` §2.1.

### 4.1 Theme frontend (`..\vvveb-landing`)

```bash
cd ../vvveb-landing
npm install            # devDependencies: gulp, gulp-file-include, gulp-sass, sass, node-sass, puppeteer…
npm run gulp           # build HTML từ src/ + SCSS → CSS

# Kiểm tra trước: sections/blocks/styles có trong commit không?
git ls-files --error-unmatch sections/sections.js blocks/blocks.js styles/styles.js
# Nếu exit 1 (thiếu) HOẶC vừa sửa src/ → chạy các lệnh dưới
npm run gulp sections  # → sections/sections.js
npm run gulp blocks    # → blocks/blocks.js
npm run gulp styles    # → styles/styles.js
npm run gulp screenshots         # (tùy chọn) thumbnail cho section
npm run gulp watch               # dev: rebuild khi file đổi
npm run gulp screenshots -hero   # chỉ nhóm cụ thể (cú pháp `-<group>`)
```

`admin/controller/editor/editor.php::loadThemeAssets()` glob `$themeFolder/{inputs,components,blocks,sections,styles}/*.js` để nạp vào editor ⇒ **thiếu các file này editor sẽ mất danh sách section/block/style mà không báo lỗi**.

### 4.2 Admin template (`..\vvveb-admin-template`)

```bash
cd ../vvveb-admin-template
npm install            # gulp, gulp-file-include, gulp-format-html, sass, node-sass
npm run gulp
```

### 4.3 VvvebJs builder

`..\VvvebJs` có `npm run gulp`, nhưng **core đã vendor sẵn bản đã build** ở `public/js/vvvebjs/`. Chỉ build khi chủ động nâng cấp builder (xem `architecture.md` §2.7) — không copy đè.

### 4.4 Build toàn bộ + tạo release (bash)

```bash
./build.sh
```

⚠️ `build.sh` sẽ:
1. `npm i` + `npm run gulp` cho mọi `public/themes/*` và `public/admin/*` có `gulpfile.js`.
2. **Xóa sạch**: `storage/compiled-templates/*`, `storage/cache/*`, `storage/logs/*`, `storage/backup/*`, `public/page-cache/*`, `public/assets-cache/*`, `public/image-cache/*`, `public/themes/*/backup/*`.
3. Tạo `vvveb.zip` (loại trừ `node_modules`, `src`, `scss`, `.git`, `config/db.php`…).

⇒ **Không dùng như bước test.** Chỉ chạy khi tạo bản phát hành.

### 4.5 Lưu ý build

- Build xong **phải vendor** sang core (`robocopy /MIR`) — build ở repo phụ không tự động xuất hiện trong core.
- **Chú ý `.gitignore` của core.** `public/themes/*/*.html` bị ignore (ngoại lệ `*/src/*.html`). Sau khi mirror, **phải** un-ignore bằng `!public/themes/*/*.html` và kiểm tra `git ls-files public/themes/landing/index.html` có nội dung. Nếu không theme sẽ không được `Themes::getList()` phát hiện.
- Sau khi vendor, `git status` sẽ hiện nhiều file thay đổi trong `public/themes/*`, `public/admin/*`: đây là điều **mong đợi**.
- Luôn ghi **commit nguồn** vào bảng `architecture.md` §2.5 và vào commit message.
- Chạy `robocopy ... /L` (dry-run) trước lần mirror đầu tiên.
- Không commit `node_modules/`, `.git/` của repo phụ.
- Kiểm tra artifact `sections/blocks/styles`: ở `a801d3a` của `..\vvveb-landing` chúng đã được track (`git ls-files`), nhưng không phải theme nào cũng vậy — luôn kiểm tra thực tế.

---

## 5. Docker

```powershell
docker compose up -d            # DB mysql + php, http://localhost:8080
docker compose -f docker-compose-apache.yaml up -d
docker compose logs -f
docker compose down             # giữ volume
docker compose down -v          # xoá cả dữ liệu DB
```

Biến môi trường dùng bởi container: `VVVEB_HOST`, `VVVEB_PASSWORD`, `DB_HOST`, `DB_DATABASE`, `DB_USER`, `DB_PASSWORD`, `DB_ENGINE`.

---

## 6. Web server

| File | Dùng khi |
|---|---|
| `apache-vvveb.conf` | Apache vhost production |
| `nginx-live.conf` | Nginx production (kèm fastcgi_cache, `VCACHE`) |
| `nginx-live-nocache.conf` | Nginx dev |
| `nginx-docker.conf` | Nginx trong Docker |
| `.htaccess` (root + `public/`) | Apache rewrite, chặn trực tiếp file `.php` |

**Bắt buộc:** DocumentRoot/`root` phải là **`public/`**, không phải repo root. Root `.htaccess` chặn mọi `.php` trừ `index.php`.

---

## 7. Debug & troubleshoot

### 7.1 Bật debug (dev)

`env.php`:
```php
define('DEBUG', true);          // hiển thị error chi tiết — TẮT ở production
define('VTPL_DEBUG', true);     // log lỗi compile vtpl
define('LOG_SQL_QUERIES', true);// log query
define('PAGE_CACHE', false);    // thấy thay đổi ngay
```

### 7.2 Log files

| Loại | Vị trí |
|---|---|
| Error log hệ thống | `storage/logs/` (xem `system/core/startup.php` handler) |
| Compiled template lỗi | bật `VTPL_DEBUG` rồi xem output/`storage/compiled-templates/` |
| Model SQL compile | `storage/model/{APP}/*.{engine}.php` |

### 7.3 Bảng triệu chứng → nguyên nhân

| Triệu chứng | Nguyên nhân thường gặp | Xử lý |
|---|---|---|
| Mọi request redirect về `/install/` | Thiếu `config/db.php` | Install (CLI hoặc browser) |
| Trang trắng / lỗi template admin | `public/admin/default` (vendor) còn rỗng | chạy checklist migration `architecture.md` §2.6 |
| Frontend không render theme | `public/themes/landing` (vendor) rỗng, hoặc `config/sites.php` trỏ theme không tồn tại | migration §2.6, hoặc đổi `theme` sang theme có thật |
| Sửa `.tpl`/HTML không có hiệu lực | compiled template cache | xóa `storage/compiled-templates/*` |
| Nội dung cũ vẫn hiện | page cache | xóa `public/page-cache/*` hoặc `PAGE_CACHE=false` |
| Plugin không load | status không `active`; hoặc admin safemode | `config/plugins.php`; kiểm tra `$admin['safemode']` |
| Editor thiếu toolbar WYSIWYG | `public/js/libs/tinymce-dist` (vendor) rỗng | migration §2.6 |
| Editor **không có** danh sách section/block/style | thiếu `public/themes/<theme>/{sections,blocks,styles}/*.js` — chưa sinh artifact trong repo theme | kiểm tra `git -C <theme-repo> ls-files sections/sections.js`; nếu thiếu thì `npm run gulp sections\|blocks\|styles` rồi vendor refresh (§1.2) |
| Vendor xong theme vẫn không render / theme không xuất hiện | **`.gitignore:9` = `public/themes/*/*.html`** làm `git add` âm thầm bỏ qua `index.html` cấp gốc | thêm `!public/themes/*/*.html` vào `.gitignore`, rồi `git add` lại; verify `git ls-files public/themes/landing/index.html` **không rỗng** |
| `git check-ignore` báo `is in submodule` (exit 128) | path còn là submodule nên git từ chối kiểm tra | thêm `--no-index`: `git check-ignore -v --no-index <path>`, hoặc chạy sau `git rm --cached` |
| Vendor refresh xong vẫn còn file cũ | dùng `Copy-Item`/`xcopy` thay vì `robocopy /MIR` | mirror lại bằng `/MIR` (xem §1.2) |
| `setConfig()` không ghi được | `config/` không writable | cấp quyền ghi (dev) hoặc dùng `Setting` (DB) |
| `SQL` model không cập nhật | `SQL_CHECK=false` hoặc cache model | đặt `SQL_CHECK=true` (dev), xóa `storage/model/*` |

### 7.4 Xác minh nhanh trước khi kết luận "bug"

```powershell
Test-Path config/db.php                                          # đã install chưa?
(Get-ChildItem public\themes\landing -Force | Measure-Object).Count   # 0 = vendor chưa migration
git ls-files -s | Select-String "^160000"                        # còn gitlink = chưa bỏ submodule
git ls-files public/themes/landing/index.html                    # rỗng = bị .gitignore nuốt mất HTML
git check-ignore -v --no-index public/themes/landing/index.html  # vì sao bị ignore
php -r "array_map('unlink', glob('storage/compiled-templates/*'));"
php -l <file-đã-sửa>
```

---

## 8. Test & QA

Hiện **không có test framework** trong repo (`tests/`, `phpunit.xml` đều không tồn tại; `.github/workflows` không có ⇒ không có CI). Do đó:

- Quy trình test bắt buộc: xem `development.md` §5.
- Kiểm tra tự động tối thiểu = `php -l` + CLI smoke (`cli.php`).
- Khi thêm test framework: ghi lại vào `vc-dev/features/<name>.md` và cập nhật mục này.

---

## 9. Đề xuất script tiện ích (`vc-dev/scripts/`)

Chưa tồn tại — tạo khi cần (mỗi script phải được tham chiếu trong plan tương ứng).

### 9.1 `vc-dev/scripts/clear-cache.ps1`

```powershell
# Xóa toàn bộ cache runtime của Vvveb
$targets = @(
  'storage/compiled-templates/*',
  'storage/cache/*',
  'storage/model/*/*.php',
  'public/page-cache/*',
  'public/assets-cache/*',
  'public/image-cache/*'
)
foreach ($t in $targets) {
  Get-ChildItem -Path $t -File -ErrorAction SilentlyContinue | Remove-Item -Force -ErrorAction SilentlyContinue
}
Write-Host "Cache cleared." -ForegroundColor Green
```

### 9.2 `vc-dev/scripts/lint.ps1`

```powershell
# php -l toàn bộ file PHP của repo (bỏ qua vendor/storage/node_modules)
$files = Get-ChildItem -Recurse -Filter *.php -File |
  Where-Object { $_.FullName -notmatch '\\(vendor|storage|node_modules)\\' }
$fail = 0
foreach ($f in $files) {
  $out = php -l $f.FullName 2>&1
  if ($LASTEXITCODE -ne 0) { Write-Host $out -ForegroundColor Red; $fail++ }
}
Write-Host ("Checked {0} files, {1} failed." -f $files.Count, $fail)
exit $fail
```

### 9.3 `vc-dev/scripts/status.ps1` (định vị môi trường)

```powershell
Write-Host "Branch:      $(git branch --show-current)"
Write-Host "Dirty files: $((git status -s | Measure-Object).Count)"
Write-Host "config/db.php exists: $(Test-Path config/db.php)"
Write-Host ".gitmodules removed:  $(-not (Test-Path .gitmodules))"
Write-Host "Gitlinks còn lại:     $((git ls-files -s | Select-String '^160000' | Measure-Object).Count)"
Write-Host "vendor/autoload.php:  $(Test-Path vendor/autoload.php)"
Write-Host "--- Thư mục vendor (0 = chưa migration) ---"
foreach ($p in 'public/themes/landing','public/admin/default','public/themes/blog-default',
               'plugins/contact-form','plugins/payment','plugins/shipping',
               'public/js/libs/tinymce-dist') {
  $n = (Get-ChildItem $p -Force -ErrorAction SilentlyContinue | Measure-Object).Count
  Write-Host ("{0,-36} {1}" -f $p, $n)
}
```

### 9.4 `vc-dev/scripts/vendor-refresh.ps1` (đề xuất)

Tự động hóa Bước 3–4 của `architecture.md` §2.4 cho một thành phần:

```powershell
param(
  [Parameter(Mandatory)][ValidateSet('landing','admin','blog-default')] [string]$Component
)
$map = @{
  'landing'      = @{ src = '..\vvveb-landing';        dst = 'public\themes\landing' }
  'admin'        = @{ src = '..\vvveb-admin-template'; dst = 'public\admin\default' }
  'blog-default' = @{ src = '..\vvveb-blog-default';   dst = 'public\themes\blog-default' }
}
$m = $map[$Component]
Write-Host "Nguồn: $($m.src)  →  $($m.dst)"
Write-Host "Commit nguồn: $(git -C $($m.src) rev-parse HEAD)   # ghi vào architecture.md §2.5"
Write-Host "--- DRY RUN (không xóa gì) ---"
robocopy $m.src $m.dst /MIR /XD .git node_modules /L /NP
$ok = Read-Host "Tiến hành mirror thật? (y/N)"
if ($ok -eq 'y') { robocopy $m.src $m.dst /MIR /XD .git node_modules /NP }
```

---

### 9.5 Thu thập bằng chứng để review PR (`development.md` §6.3)

Review PR là bước **bắt buộc** trước merge. Bộ lệnh tối thiểu:

```powershell
# PR đang review
gh pr view <n> --json title,body,files,additions,deletions
gh pr diff <n>
gh pr view <n> --json commits --jq '.commits[].oid'
gh pr list --state open

# Xác minh claim về repo/vendor
git ls-files -s | Select-String "^160000"                       # còn gitlink?
Test-Path .gitmodules                                            # còn submodule?
git ls-files public/themes/landing/index.html                    # HTML có bị ignore?
git check-ignore -v --no-index <path>                            # lý do ignore (--no-index bắt buộc khi còn submodule)
(Get-ChildItem public/themes/landing -Force | Measure-Object).Count
git -C ..\vvveb-landing rev-parse --short HEAD                   # commit nguồn thực tế
gh repo view honggiang13/vvveb-blog-default --json name           # tên canonical

# Ghi kết quả + commit
# vc-dev/pr-review/pr-<n>-<slug>.md  (mẫu: development.md §6.3)
git add vc-dev/pr-review/
git commit -m "docs(vc-dev): review PR #<n> — <verdict>"

# Lưu ý: gh pr review --approve sẽ fail nếu trùng tài khoản tác giả
#   → file review trong repo là bằng chứng chính thức
```

Quy tắc: reviewer phải **độc lập** (session/agent khác tác giả), **tự xác minh** từng finding trước khi sửa, và **không merge** khi verdict còn `HOLD`/`CHANGES REQUIRED`.

---

## 10. Tài liệu tham chiếu ngoài

- Docs chính thức: https://docs.vvveb.com/
- Upstream core: https://github.com/givanz/Vvveb
- Fork core: https://github.com/honggiang13/Vvveb
- Repo phụ (fork của ta): `honggiang13/vvveb-landing` · `honggiang13/vvveb-admin-template` · `honggiang13/vvveb-blog-default` · `honggiang13/VvvebJs`
- Upstream repo vendor: `givanz/landing` · `givanz/vvveb-admin-template` · `givanz/blog-default` · `Vvveb/contact-form` · `Vvveb/payment` · `Vvveb/shipping` · `tinymce/tinymce-dist`
- Ghi chú: `honggiang13/landing` → đổi tên thành `honggiang13/vvveb-landing` (GitHub redirect cả hai; dùng tên **mới**). Thư mục local cũng đã đổi thành `..\vvveb-landing` (2026-10-21) — xem `architecture.md` §2.1. Blog-default fork cũng có tên repo khác: dùng **`honggiang13/vvveb-blog-default`** thay vì `honggiang13/blog-default`.
