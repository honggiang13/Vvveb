# Architecture — Vvveb Fork (vcenter-vvveb)

> Scope: kiến trúc hệ thống, đánh giá multi-repo, chiến lược quản lý source code.
> Đọc file này **trước** khi bắt đầu bất kỳ task nào.
> Cập nhật lần cuối: 2026-10-21 — chốt **Phương án D (vendor hóa, bỏ submodule)**; xem §2.

---

## 1. Tổng quan hệ thống

Vvveb là CMS/headless CMS viết bằng PHP thuần (không framework ngoài), kiến trúc
**app + system + public** tách biệt, mở rộng qua **plugins** và **themes** bằng
event system nội bộ.

| Thuộc tính | Giá trị |
|---|---|
| Version core | `V_VERSION = 0.0.7` (`index.php:28`) — **lưu ý** `cli.php:71` còn ghi `0.0.1` (inconsistency có sẵn của upstream) |
| PHP | >= 7.4 (khuyến nghị 8.3+); ext: mysqli/sqlite3/pgsql, xml, pcre, zip, dom, curl, gettext, gd\|imagick |
| Database | MySQL 5.7+/MariaDB 10.2+, SQLite, PostgreSQL 11+ |
| License | AGPL-3.0-or-later |
| Upstream | `https://github.com/givanz/Vvveb` |
| Fork origin | `https://github.com/honggiang13/Vvveb` |
| Entry point public duy nhất | `/public/index.php` (+ wrapper admin/rest/graphql/install/vadmin) |
| Request hiện tại | `admin/` → `APP=admin`, `PUBLIC_THEME_PATH=/public/admin/` |

### Sơ đồ thành phần

```
Browser
  │
  ├─ /public/index.php ─┐
  ├─ /public/admin/  ───┤  (thin wrapper, set APP + PUBLIC_THEME_PATH)
  ├─ /public/vadmin/ ───┤  (custom admin path, ta có thể đổi tên để tránh bot)
  ├─ /public/rest/   ───┤  (REST=false mặc định)
  └─ /public/install/ ──┘
                        │
                    /index.php  (bootstrap: env.php → chọn APP → startup.php)
                        │
        ┌───────────────┴────────────────┐
        │  system/core/startup.php       │  constants, autoloader, error handler,
        │                                │  DB config, SITE_ID/URL/PATH, load plugins
        └───────────────┬────────────────┘
                        │
        ┌───────────────┴───────────────────────────────────────────┐
        │ system/core/frontcontroller.php  → match route             │
        │ system/routes.php  ← config/{app}-routes.php (cached)      │
        └───────────────┬───────────────────────────────────────────┘
                        │
      ┌─────────────────┼──────────────────┬─────────────────┐
      │                 │                  │                 │
   app/             admin/             rest/            graphql/
 (frontend)      (backend CMS)      (REST API)       (GraphQL API)
 controller/      controller/         controller/      controller/
 template/*.tpl   template/*.tpl      sql/  validate/   sql/
 sql/{engine}     sql/{engine}
 component/       component/
      │                 │
      └────────┬────────┘
               │  system/vtpl  (compile HTML + .tpl → PHP, cache ở storage/compiled-templates)
               │
        public/themes/<theme>/ (frontend HTML+assets)
        public/admin/<theme>/  (admin HTML+assets)
        public/plugins/<plugin>/ (plugin public assets)
```

### Luồng request (frontend)

1. `index.php` → `env.php` (hằng số runtime) → chọn `APP` (`app` mặc định; `rest`/`graphql` nếu bật trong `env.php` và URI khớp; `install` nếu thiếu `config/db.php`).
2. `system/page-cache.php` (nếu `PAGE_CACHE && APP=='app'`) phục vụ cache tĩnh, ngược lại gọi `System\Core\start()`.
3. `startup.php`: define `DIR_STORAGE`, `DIR_THEMES`, `DIR_PLUGINS`, autoloader (`Vvveb\App\*` → `app/`, `Vvveb\Admin\*` → `admin/`, `Vvveb\Plugins\X\*` → `plugins/x/`), đọc `config('db.connections.<default>')`, `Sites::getSiteData()` → `SITE_ID/SITE_URL/…`.
4. `Plugins::loadPlugins(SITE_ID)` (chỉ với `APP != 'admin'`), `admin/controller/base.php` tự gọi `Plugins::loadPlugins($site_id)` khi không ở safemode (**rất quan trọng**: plugin admin hooks). Xem `arch-audit` §5.
5. `FrontController::dispatch($site)` → `system/routes.php` (đọc `config/{app}-routes.php`, trigger event `Routes::init`, cache bảng route) → `FrontController::call()` load `{app}/controller/base.php`, chạy `init()`, chọn `.html` template, gọi action, trigger `FrontController::call`, gửi response.

### Luồng admin

`admin/controller/base.php` xử lý: auth + permission, build menu (`Event::trigger('Vvveb\Controller\Base','init-menu')`), load plugins, `$this->view->global` (site hiện tại, user, admin path, capabilities). `admin/controller/listing.php` và `crud.php` là base class tái sử dụng cho mọi module quản trị.

---

## 2. Multi-Repo Assessment & Chiến lược tích hợp

### 2.1 Tình trạng hiện tại (2026-10-21, sau khi fork bổ sung)

| Path | Upstream gốc | Fork của ta (cùng cấp) | Gitlink superproject ghi | Local checkout | Trạng thái thư mục |
|---|---|---|---|---|---|
| `public/themes/landing` | givanz/landing | ✅ `..\landing` → **`honggiang13/vvveb-landing`** (repo đã đổi tên từ `landing`) | `431a650` | `a801d3a` (HEAD, local mới hơn) | ❌ rỗng |
| `public/admin/default` | givanz/vvveb-admin-template | ✅ `..\vvveb-admin-template` → `honggiang13/vvveb-admin-template` | `4cdf289` | `2394323` (HEAD, local mới hơn) | ❌ rỗng |
| `public/themes/blog-default` | givanz/blog-default | ✅ **`honggiang13/blog-default`** (mới fork thêm) | `e2eb5f7` | — | ❌ rỗng |
| `plugins/contact-form` | Vvveb/contact-form | ❌ | `0412f5d` | — | ❌ rỗng |
| `plugins/payment` | Vvveb/payment | ❌ | `86742ac` | — | ❌ rỗng |
| `plugins/shipping` | Vvveb/shipping | ❌ | `6612fd9` | — | ❌ rỗng |
| `public/js/libs/tinymce-dist` | tinymce/tinymce-dist | ❌ | `3d62192` | — | ❌ rỗng |

Repo phụ (không phải submodule):

| Repo phụ (cùng cấp) | Đích trong core | Cơ chế hiện tại |
|---|---|---|
| `..\VvvebJs` (`honggiang13/VvvebJs`) | `public/js/vvvebjs` | **vendor copy** — đã tích hợp sẵn, bản core đã patch khác hash bản raw |

**Kết luận: repo chính CHƯA tích hợp đủ.** Hệ quả thực tế của 7 thư mục rỗng:

- ❌ Không render được frontend: `config/sites.php` trỏ theme `landing` (đang rỗng), và không có `config/db.php` ⇒ request thật sẽ redirect sang installer.
- ❌ Không render được admin: template HTML của admin nằm trong `public/admin/default` (đang rỗng). Chỉ có `admin/template/*.tpl` (file lệnh selector, không phải HTML).
- ❌ Plugin ecommerce/contact form không load được (`plugins/{contact-form,payment,shipping}` rỗng). `config/plugins.php` khai báo `contact-form` = active cho site 1.
- ⚠️ `public/js/libs/tinymce-dist` rỗng ⇒ editor WYSIWYG của admin thiếu runtime.
- ✅ **Không thiếu phần VvvebJs JS page builder**: core đã vendor sẵn bản builder đã build/hardened tại `public/js/vvvebjs/` (34 file: `builder.js`, `components-*.js`, `plugin-*.js`, `section.js`, `undo.js`, `icons/`, `components/`). Bản raw `..\VvvebJs\libs\builder\builder.js` **khác hash** ⇒ đây là nguồn tham khảo/upstream, KHÔNG copy đè.
- ⚠️ `public/themes/default/` chỉ có `feed/` (XSL feed), không phải theme đầy đủ.

### 2.2 Quyết định: PHƯƠNG ÁN D — Vendor hóa, bỏ hoàn toàn submodule

> **Đây là chiến lược đã chốt của dự án.** Không còn dùng git submodule cho bất kỳ thành phần nào.

**Lý do chọn:**

1. Loại bỏ vĩnh viễn failure mode nghiêm trọng nhất của dự án: clone xong nhưng hệ thống **không chạy được** vì thiếu submodule.
2. Repo tự chứa (self-contained) ⇒ CI/CD, Docker build, deploy không cần `--recurse-submodules`, không cần mạng tới 4+ repo ngoài.
3. Đúng tinh thần **Minimal Core Code Modification** ở tầng repo: một nguồn sự thật duy nhất cho mọi artifact đã build của UI.
4. Các repo phụ đã được fork về `honggiang13` ⇒ quyền kiểm soát nằm trong tay dự án, không phụ thuộc repo bên thứ ba.

**Đánh đổi đã chấp nhận:**

- Mất khả năng "1 dòng lệnh" để cập nhật repo phụ ⇒ thay bằng **vendor refresh có kiểm soát** (quy trình §2.4).
- Rủi ro "fork drift": bản vendor trong core có thể lệch upstream; giảm thiểu bằng cách **luôn ghi lại commit nguồn** khi vendor (bảng §2.5).
- Tăng dung lượng repo (đặc biệt `tinymce-dist`).

### 2.3 Trạng thái target sau khi vendor hóa

| Path | Nguồn vendor | Commit nguồn chốt | Git quản lý |
|---|---|---|---|
| `public/themes/landing` | `honggiang13/vvveb-landing` | `a801d3a` (HEAD local, mới hơn gitlink `431a650`) | ✅ file thường |
| `public/admin/default` | `honggiang13/vvveb-admin-template` | `2394323` (HEAD local, mới hơn gitlink `4cdf289`) | ✅ file thường |
| `public/themes/blog-default` | `honggiang13/blog-default` | `e2eb5f7` | ✅ file thường |
| `plugins/contact-form` | `Vvveb/contact-form` | `0412f5d` | ✅ file thường |
| `plugins/payment` | `Vvveb/payment` | `86742ac` | ✅ file thường |
| `plugins/shipping` | `Vvveb/shipping` | `6612fd9` | ✅ file thường |
| `public/js/libs/tinymce-dist` | `tinymce/tinymce-dist` | `3d62192` | ✅ file thường |
| `public/js/vvvebjs` | `honggiang13/VvvebJs` | (bản core đã patch, không map 1-1) | ✅ file thường (đã vậy) |

`.gitmodules` bị **xóa hoàn toàn** (`git rm .gitmodules`). Không còn `git submodule` trong bất kỳ quy trình nào.

### 2.4 Quy trình Vendor Refresh (thay thế "submodule update")

Vendor hóa **không** có nghĩa là đóng băng vĩnh viễn. Khi cần nâng cấp một thành phần:

**Bước 0 — Tạo plan bắt buộc** (xem `development.md` §4):
`vc-dev/features/vendor-refresh-<component>.md` hoặc `vc-dev/issues/vendor-refresh-<component>.md`.

**Bước 1 — Lấy bản mới của repo phụ** (làm ở repo phụ, KHÔNG trong core):
```powershell
git -C ..\vvveb-landing fetch origin; git -C ..\vvveb-landing checkout <commit-or-branch>
git -C ..\vvveb-landing rev-parse HEAD      # ghi lại vào plan: commit nguồn
```

**Bước 2 — Build trước khi copy** (nếu repo phụ cần build):
```powershell
cd ..\vvveb-landing; npm i; npm run gulp            # sinh HTML + CSS vào chính repo phụ
cd ..\vvveb-admin-template; npm i; npm run gulp
```

**Bước 3 — Đồng bộ vào core bằng mirror có kiểm soát:**
```powershell
# Dùng robocopy /MIR cho đúng ngữ nghĩa "thay thế toàn bộ nội dung"
robocopy ..\vvveb-landing public\themes\landing /MIR /XD .git node_modules /XF *.log
robocopy ..\vvveb-admin-template public\admin\default /MIR /XD .git node_modules /XF *.log
```
Cờ `/MIR` = mirror (xóa file không còn tồn tại ở nguồn) — đúng cho vendor hóa; `/XD .git node_modules` để **không** mang metadata git của repo phụ vào core.

**Bước 4 — Kiểm tra artifact sinh tự động trong theme:** `sections/sections.js`, `blocks/blocks.js`, `styles/styles.js` là **build artifact** của theme (sinh bởi `vvveb.js:419`, `:464` — ghi vào `<theme>/<type>s/<type>s.js`), `admin/controller/editor/editor.php::loadThemeAssets()` glob `$themeFolder/{inputs,components,blocks,sections,styles}/*.js` để nạp vào editor. ⇒ **Bắt buộc** chạy `npm run gulp sections`, `npm run gulp blocks`, `npm run gulp styles` (và `screenshots` nếu cần thumbnail mới) trước khi vendor, nếu không editor sẽ mất danh sách section/block.

**Bước 5 — Verify rồi mới commit:**
```powershell
php -r "array_map('unlink', glob('storage/compiled-templates/*'));"
php -r "array_map('unlink', glob('storage/cache/*'));"
php -r "array_map('unlink', glob('public/page-cache/*'));"
```
Smoke test: frontend (theme landing + blog-default), admin dashboard, editor (list section/block/style), WYSIWYG tinymce, activate/deactivate `contact-form`.
Rồi cập nhật bảng §2.5 và commit vào `vc-dev` **với commit nguồn ghi trong commit message**:
```
chore(theme/landing): vendor refresh lên a801d3a

Nguồn: honggiang13/vvveb-landing@a801d3a
Đã build: gulp (html/css), gulp sections, gulp blocks, gulp styles
Refs: vc-dev/features/vendor-refresh-landing.md
```

### 2.5 Bảng theo dõi phiên bản vendor (BẮT BUỘC cập nhật)

| Thành phần | Repo nguồn | Commit đã vendor | Ngày vendor | Đã build? | Người thực hiện |
|---|---|---|---|---|---|
| `public/themes/landing` | honggiang13/vvveb-landing | _chờ migration_ | — | — | — |
| `public/admin/default` | honggiang13/vvveb-admin-template | _chờ migration_ | — | — | — |
| `public/themes/blog-default` | honggiang13/blog-default | _chờ migration_ | — | — | — |
| `plugins/contact-form` | Vvveb/contact-form | _chờ migration_ | — | — | — |
| `plugins/payment` | Vvveb/payment | _chờ migration_ | — | — | — |
| `plugins/shipping` | Vvveb/shipping | _chờ migration_ | — | — | — |
| `public/js/libs/tinymce-dist` | tinymce/tinymce-dist | _chờ migration_ | — | — | — |

Kiểm tra nhanh khi nghi ngờ drift: đối chiếu `git -C ..\<repo-phụ> rev-parse HEAD` với cột "Commit đã vendor".

### 2.6 Lệnh migration (một lần, chạy trong core) — checklist

```powershell
# 0. Đảm bảo working tree sạch
git status -s

# 1. Clone repo phụ còn thiếu ở commit cần thiết (cả 4 thành phần upstream)
git clone --no-checkout https://github.com/honggiang13/blog-default.git "$env:TEMP\vend\blog-default"
git clone --no-checkout https://github.com/Vvveb/contact-form.git      "$env:TEMP\vend\contact-form"
git clone --no-checkout https://github.com/Vvveb/payment.git           "$env:TEMP\vend\payment"
git clone --no-checkout https://github.com/Vvveb/shipping.git          "$env:TEMP\vend\shipping"
git clone --no-checkout https://github.com/tinymce/tinymce-dist.git    "$env:TEMP\vend\tinymce-dist"
# với mỗi repo: git -C <dir> checkout <gitlink-commit ở bảng §2.1>

# 2. Mirror 7 thành phần vào core (xem Bước 3 §2.4 cho cú pháp robocopy /MIR)

# 3. Bỏ cơ chế submodule
git rm --cached .gitmodules
git rm --cached public/themes/landing public/admin/default public/themes/blog-default `
               plugins/contact-form plugins/payment plugins/shipping public/js/libs/tinymce-dist
Remove-Item .gitmodules
Remove-Item -Recurse -Force public\themes\landing\.git, public\admin\default\.git, `
  public\themes\blog-default\.git, plugins\contact-form\.git, plugins\payment\.git, `
  plugins\shipping\.git, public\js\libs\tinymce-dist\.git -ErrorAction SilentlyContinue

# 4. Thêm lại dưới dạng file thường
git add public/themes/landing public/admin/default public/themes/blog-default `
        plugins/contact-form plugins/payment plugins/shipping public/js/libs/tinymce-dist

# 5. Xác minh: KHÔNG còn bất kỳ dấu hiệu submodule nào
git submodule status                                   # phải trống
git ls-files -s | Select-String "^160000"              # phải trống (160000 = gitlink)
Test-Path .gitmodules                                  # phải False

# 6. Cập nhật bảng §2.5 rồi commit
```

**Lưu ý quan trọng:** bước `git rm --cached` chỉ gỡ gitlink/metadata; nội dung file thật được thêm lại ở bước 4. Với `.git` của repo phụ, **bắt buộc xóa** trước khi `git add`, nếu không git sẽ từ chối hoặc tạo nested repo.

### 2.7 Đồng bộ VvvebJs (page builder)

Core **không** dùng submodule cho builder — đã ở dạng vendor copy từ đầu. Khi cần cập nhật từ `..\VvvebJs`:

- **Không** copy toàn bộ `libs/builder` đè lên `public/js/vvvebjs` (bản core đã được tùy biến: thêm `components-content.js`, `components-ecommerce.js`, `plugin-media.js`, `elements-changes.js`, `resources.js`, `components-server.js`…).
- Quy trình đúng: diff từng file → chọn lọc thay đổi cần thiết → test trong admin editor (`/?module=/editor/editor`) → PR vào `vc-dev`.
- Xem thêm `public/js/libs/*`: `swiper`, `coloris`, `glightbox` cũng là copy vendor (không bao giờ nằm trong `.gitmodules`).
- Cập nhật bảng §2.5 không áp dụng cho VvvebJs (không map 1-1); ghi lại patch trong `vc-dev/features/*.md` thay thế.

---

## 3. Kiến trúc cốt lõi — các điểm mở rộng (extension points)

Đây là **danh sách vàng**: mọi tính năng mới nên bám vào các điểm này thay vì sửa core.

### 3.1 Event system (`system/event.php`)

```php
Event::on($namespace, $name, $id, $callback, $priority = 1000);  // đăng ký
list($a, $b) = Event::trigger($namespace, $name, $a, $b);        // phát, filter-style
Event::off($namespace, $name, $eventId);                          // hủy
```

- Callback trả về `null` ⇒ giữ nguyên tham số trước đó (an toàn).
- Namespace/name thường là `__CLASS__` + `__FUNCTION__` của điểm trigger.

Các điểm trigger quan trọng đã tồn tại trong core:

| Namespace | Name | File | Dùng để |
|---|---|---|---|
| `Vvveb\Controller\Base` | `init-menu` / `init-menu-menu` | `admin/controller/base.php` | Thêm/sửa menu admin |
| `Vvveb\System\Routes` | `init` | `system/routes.php` | Thêm route mới |
| `Vvveb\System\Core\FrontController` | `call` | `system/core/frontcontroller.php` | Đổi template/controller/action |
| `Vvveb\Controller\Base` | `init` (app/rest/graphql) | `*/controller/base.php` | Bơm biến global vào view |
| `Vvveb\postTypes` | `customPost` / `customProduct` | `system/functions.php`, `admin/controller/base.php` | Đăng ký custom post/product type |
| `Vvveb\System\Extensions\Plugins` | `setup`, `activate`, `deactivate` | `system/extensions/plugins.php` | Vòng đời plugin |
| `admin-bar` | `menu`, `top` | `admin/admin-bar.php` | Thêm item vào admin bar |
| `Vvveb\System\Extensions\Themes` | (xem `system/extensions/themes.php`) | | Vòng đời theme |

Xem bảng đầy đủ 47 điểm trigger bằng:
`grep -rn "Event::\(on\|trigger\)(" --include=*.php .`

### 3.2 Plugin system

- Thư mục: `plugins/<slug>/` với file bắt buộc `plugin.php`.
- Metadata đọc từ **doc-comment** `Key: value` (`Extensions::KEY_VALUE_REGEX`), key ví dụ: `Name`, `Description`, `Version`, `Author`, `Category`, `Settings`, `Thumb`.
- Trạng thái bật/tắt lưu ở `config/plugins.php` (`plugins.{site_id}.{slug}.status = active|inactive`) — ghi qua `setConfig()`.
- Load: `Plugins::loadPlugins($site_id)` → `include plugins/<slug>/plugin.php` cho plugin `active`. Được gọi ở `startup.php` (frontend) **và** `admin/controller/base.php` (admin, trừ safemode).
- Vòng đời: `activate()` (trigger `setup` lần đầu) → `deactivate()` → `install/uninstall`.
- Cấu trúc khuyến nghị cho plugin mới:
  ```
  plugins/my-plugin/
  ├── plugin.php                  # metadata + Event::on(...) đăng ký hooks
  ├── admin/controller/…          # controller giao diện quản trị
  ├── admin/template/…            # file .tpl
  ├── app/controller/…            # controller frontend
  ├── app/template/…
  ├── public/                     # copy → public/plugins/my-plugin khi activate
  └── sql/{mysqli,pgsql,sqlite}/  # model SQL nếu cần
  ```
- Routing vào plugin: `Vvveb\Plugins\<Name>\Controller\...`, path controller ở `plugins/<slug>/{app}/controller` (module `plugins/<slug>/<controller>`).
- Public assets: `Plugins::copyPublicDir()` copy `plugins/<slug>/public` → `public/plugins/<slug>` khi activate (trừ khi là symlink).

### 3.3 Thanh toán / vận chuyển

- `system/payment.php` — singleton registry: `registerMethod()`, `getMethods()`, `setMethod()`, `authorize()`; base class `system/payment-method.php`.
- `system/shipping.php` — đối xứng: `registerMethod()`, `getMethods()`, `ship()`; base class `system/shipping-method.php`.
- `app/controller/checkout/checkout.php` trigger `__CLASS__::add` và `add:after` cho plugin can thiệp.
- Khi viết plugin thanh toán mới: tạo class kế thừa `PaymentMethod`, đăng ký trong `plugin.php`, và đọc config từ `Setting`/`config()`.

### 3.4 Theme engine

- Theme nằm ở `public/themes/<slug>/`: HTML pages + assets + `theme.json` (metadata UI) + `gulpfile.js` (build SCSS/partials).
- `theme.json` khai báo: `cssFramework` (`bootstrap5`…), `jsFramework` (`vanilla`…), `pages`, `sections`, `blocks`, `components`, `inputs`, `ignoreFolders`, `dark`.
- Phát hiện theme: `Themes::getList()` glob `public/themes/*/index.html` → parse metadata comment trong `theme.json`.
- Gán theme cho site: `config/sites.php` (key dạng host/path wildcard, ví dụ `'* * *'`) với field `theme`.
- Admin theme: `config/admin.php` → `'theme' => 'default'`; HTML ở `public/admin/default`.

### 3.5 vtpl template engine

`system/vtpl/{vtpl.php,macros.php,debug.php}` — compiler dựa trên DOMDocument/XPath:
- Ghép HTML của theme với file lệnh `.tpl` (`.tpl` chứa selector + lệnh, không phải HTML).
- Cú pháp: selector CSS/XPath với thuộc tính `data-v-*`, modifiers, `import(...)`, alias, chèn `|before`/`|after`.
- Output PHP đã compile cache tại `storage/compiled-templates/{app}_{site}_{theme}_{template}[-edit]`, tự recompile khi mtime HTML/TPL mới hơn.
- Chế độ editor giữ `data-v-*`; chế độ render thường strip các attribute đó.

### 3.6 Config & Settings

Hai tầng, **đừng lẫn lộn**:

| Tầng | API | Lưu ở | Dùng cho |
|---|---|---|---|
| File config | `config('ns.key.sub', $default)` / `setConfig()` | `config/<ns>.php` | routes, admin theme, sites, plugin status, mail |
| DB settings | `Setting::getSetting()` / `setSetting($ns, $key, $val)` | bảng DB, theo site | cấu hình người dùng thay đổi thường xuyên |

- `Config::loadConfig()` lazy-load `config/<namespace>.php`; `Config::saveConfig()` ghi bằng `var_export` + `opcache_invalidate`.
- ⚠️ `config/app.php` và (nếu có) `config/db.php` chứa **key/cronkey và credential** ⇒ không commit secret mới, không log giá trị này.
- `env.php` là feature-flag toàn cục: `SQL_CHECK`, `PAGE_CACHE`, `DEBUG`, `VTPL_DEBUG`, `GETTEXT`, `REST`, `GRAPHQL`, `V_SUBDIR_INSTALL`, `V_SHARED_SESSION`, `LOG_SQL_QUERIES`, `DISABLE_PLUGIN_ON_ERORR`.

---

## 4. Chiến lược quản lý source code

### 4.1 Branch model

```
upstream/main (givanz/Vvveb)
        │  (fetch + merge/rebase, chỉ khi cần bảo mật/bugfix)
        ▼
origin/master   ← baseline THUẦN, KHÔNG commit thay đổi riêng
        │
        ▼
origin/vc-dev   ← branch tích hợp chính, nhận mọi PR tính năng
        │
        └─ feature/<name>, fix/<id>  ← branch ngắn hạn, sống < 1 tuần
```

Trạng thái hiện tại: `vc-dev` **đang trùng** `master` (không có diff, không có commit riêng) ⇒ baseline sạch, có thể fast-forward từ upstream bất cứ lúc nào.

**Quy tắc:**
1. `master` là baseline thuần upstream. Không commit trực tiếp. Không rebase/merge `vc-dev` vào `master`.
2. `vc-dev` là nơi tích hợp; chỉ nhận merge từ feature/fix branch sau review.
3. Khi có bản upstream mới: `git fetch upstream && git checkout master && git merge --ff-only upstream/master`, sau đó `git checkout vc-dev && git merge master`.
4. Mọi thay đổi **phải** giữ được khả năng merge/rebase sạch từ upstream ⇒ ưu tiên file **mới**, tránh sửa file core.

### 4.2 Minimal Core Modification — thứ tự ưu tiên

Khi cần thêm tính năng, chọn theo thứ tự sau (dừng ở mức thấp nhất đáp ứng được yêu cầu):

| Mức | Cách làm | Ví dụ | Đụng core? |
|---|---|---|---|
| 1 | Thêm **plugin** mới trong `plugins/` | Form liên hệ mới, cổng thanh toán | ❌ |
| 2 | Thêm **theme** mới trong `public/themes/` | Landing page mới | ❌ |
| 3 | **Config** + file mới ở thư mục không phải core | Route, site, admin theme | ❌ |
| 4 | Đăng ký **event hook** (`Event::on`) | Thêm menu admin, custom post type | ❌ |
| 5 | **Create-only**: thêm file mới trong `system/`, `app/`, `admin/` | Driver mới, controller mới | ⚠️ thấp (không đổi file cũ) |
| 6 | Sửa file core hiện có | — | ✅ **BẮT BUỘC có lý do + ghi lại trong Plan** |

**Được phép không cần phê duyệt:** mức 1–5.
**Mức 6 bắt buộc:** ghi vào `## Scope & Impact Analysis` của Plan (lý do, không có extension point nào dùng được, kế hoạch rollback), và gắn comment đánh dấu để dễ rebase:

```php
// >>> vc-dev:patch <feature-slug> — <lý do> (upstream: <link issue/PR nếu có>)
// <<< vc-dev:patch
```

Comment marker này cho phép tìm nhanh khi rebase: `grep -rn "vc-dev:patch" --include=*.php`.

### 4.3 Quy tắc bất di bất dịch

- ❌ **Không sửa** `vendor/` (chỉ có `autoload.php`), `storage/*` (runtime data), `public/*-cache/*`.
- ❌ **Không commit** `config/db.php`, `config/app.php` (secret), `*.zip` artifact.
- ❌ **Không sửa trực tiếp** nội dung thư mục vendor (landing, admin/default, blog-default, contact-form, payment, shipping, tinymce-dist) trong core: mọi thay đổi phải phát sinh ở repo phụ rồi **vendor refresh** theo quy trình `architecture.md` §2.4.
- ❌ **Không commit** metadata git của repo phụ (`.git/`, `node_modules/`) vào core khi vendor hóa.
- ❌ **Không tạo file build/generated** tại sai vị trí: HTML/CSS build của theme/admin được sinh bởi gulp **trong repo phụ**, sau đó vendor sang core.
- ✅ Mọi thay đổi core (mức 6) phải ghi kèm marker comment và **1 dòng trong `vc-dev/CHANGELOG-core-patches.md`** (xem `system/core/startup.php` patch list — tạo file này khi có patch đầu tiên).

---

## 5. Bản đồ thư mục nhanh

| Đường dẫn | Vai trò | Được sửa? |
|---|---|---|
| `index.php`, `cli.php` | Bootstrap web/CLI | ⚠️ hạn chế |
| `env.php` | Feature flags runtime | ✅ (nhưng cẩn thận production) |
| `config/*.php` | Cấu hình app/site/route/plugin | ✅ trừ `db.php`, `app.php` secret |
| `system/**` | Core framework | ⚠️ mức 6 |
| `app/**` | Frontend app (controller/template/sql) | ⚠️ mức 5–6 |
| `admin/**` | Admin app | ⚠️ mức 5–6 |
| `rest/**`, `graphql/**` | API apps | ⚠️ mức 6 |
| `install/**` | Installer | ⚠️ mức 6 |
| `plugins/**` | Plugin — gồm 3 plugin vendor (contact-form, payment, shipping) + plugin của ta | ✅ plugin của ta; vendor qua §2.4 |
| `public/themes/**` | Theme frontend — `landing`, `blog-default` là **vendor** | ❌ sửa trực tiếp; vendor qua §2.4 |
| `public/admin/**` | Theme admin — `default` là **vendor** | ❌ sửa trực tiếp; vendor qua §2.4 |
| `public/js/vvvebjs/**` | VvvebJs page builder (vendor + patch) | ⚠️ mức 6, patch có kiểm soát |
| `public/js/libs/**` | Lib bên thứ ba | ❌ |
| `public/resources/svg/icons/**` | 24 bộ icon (vendor) | ❌ |
| `storage/**` | Cache/compiled/logs/backup (gitignored) | ❌ |
| `vc-dev/**` | Tài liệu + plan/tasklist | ✅ tự do |

---

## 6. Trạng thái môi trường hiện tại (Checklist trước khi dev)

| Hạng mục | Trạng thái | Hành động |
|---|---|---|
| `config/db.php` | ❌ chưa có | chạy installer hoặc `php cli.php install …` |
| `composer install` | ⚠️ `vendor/` chỉ có `autoload.php` | `composer install` (không có dependency thật, an toàn) |
| Node/npm | ❌ chưa có `node_modules` | chỉ cần ở **repo phụ** khi vendor refresh (xem `skills.md` §4) |
| Vendor hóa (Phương án D) | 🔄 **chưa thực hiện** — 7/7 thư mục còn rỗng, còn là gitlink | chạy checklist migration §2.6 |
| `.gitmodules` | ⚠️ còn tồn tại (chưa đổi URL sang fork `honggiang13`) | xóa hoàn toàn khi migration §2.6 — không cần sửa URL nữa vì Phương án D bỏ submodule |
| `vc-dev/` docs | ✅ đã tạo | — |

---

## 7. Ghi chú đánh giá kiến trúc (rủi ro & cơ hội)

**Rủi ro:**
1. ~~Fork phụ thuộc submodule ngoài ⇒ không build được offline~~ → **đã xử lý bằng Phương án D** (vendor hóa §2.2–§2.6). Rủi ro thay thế: **vendor drift** — bản copy trong core lệch repo phụ mà không ai biết ⇒ giảm thiểu bằng bảng theo dõi §2.5 (bắt buộc cập nhật mỗi lần vendor refresh).
2. `vc-dev` hiện = `master` ⇒ chưa có "lớp bảo vệ" nào cho thay đổi riêng; cần bắt đầu bằng commit đầu tiên vào `vc-dev`.
3. `public/js/vvvebjs` đã bị patch so với upstream VvvebJs ⇒ cập nhật builder cần merge thủ công, không thể copy đè.
4. `config/app.php` chứa `key`/`cronkey` trong working tree (đang commit) ⇒ nếu fork sẽ public, **phải rotate + chuyển sang `.env`-style ngoài git**.
5. `SQL_CHECK=true` + `PAGE_CACHE=true` là default thiên về dev; production phải tắt để tránh ghi cache liên tục.
6. Vendor hóa làm tăng dung lượng repo (đặc biệt `tinymce-dist`) ⇒ cần cân nhắc khi push/pull lần đầu.
7. Theme vendor **phải được build trước khi copy** (HTML/CSS + `sections/`, `blocks/`, `styles/` lists) — nếu quên `gulp sections|blocks|styles`, editor VvvebJs sẽ mất danh sách section/block mà không báo lỗi rõ ràng.

**Cơ hội:**
- Event system + plugin loader đủ mạnh ⇒ gần như mọi tính năng đều đạt mức 1–4 (không đụng core).
- vtpl cho phép override template bằng file `.tpl` bổ sung ⇒ tùy biến giao diện admin/frontend không cần fork theme.
- `cli.php` cho phép script hóa install/activate plugin/import/cron ⇒ CI/CD và automation không cần browser.
- Repo tự chứa sau vendor hóa ⇒ Docker build và CI không cần mạng tới repo ngoài, deploy một artifact là đủ.

---

## 8. Tham chiếu

- Upstream core: https://github.com/givanz/Vvveb · Docs: https://docs.vvveb.com/
- Fork core: https://github.com/honggiang13/Vvveb
- Repo phụ (fork của ta): `honggiang13/vvveb-landing` (đã đổi tên từ `landing`), `honggiang13/vvveb-admin-template`, `honggiang13/blog-default`, `honggiang13/VvvebJs`
- Upstream của repo vendor: `givanz/landing`, `givanz/vvveb-admin-template`, `givanz/blog-default`, `tinymce/tinymce-dist`, `Vvveb/contact-form`, `Vvveb/payment`, `Vvveb/shipping`
- Ghi chú: `honggiang13/landing` và `honggiang13/vvveb-landing` trỏ cùng một repo (GitHub redirect sau khi đổi tên) — dùng tên mới. Cả hai remote trả về cùng HEAD `a801d3a`.
- Audit chi tiết đã thực hiện: session `ses_ea3475440ffeNpHR2uIS1MTX6m` (2026-10-21).
