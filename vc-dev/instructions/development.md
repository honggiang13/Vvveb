# Development Guide — vc-dev workflow

> Quy trình phát triển chuẩn cho Human Developers & AI Agents.
> Nguyên tắc gốc: **Minimal Core Code Modification** (xem `architecture.md` §4.2).
> Mục lục: [1. Setup](#1-thiết-lập-môi-trường-local) · [2. Workflow](#2-workflow-phát-triển-tiêu-chuẩn) · [3. Mở rộng (plugin/config/theme)](#3-hướng-dẫn-mở-rộng-theo-thứ-tự-ưu-tiên-ít-can-thiệp-core) · [4. Planning & Tasklist](#4-quy-trình-planning-tasklist--multi-agent-handoff) · [5. Testing](#5-testing) · [6. PR & Review](#6-pull-request--review) · [7. Docs](#7-documentation-update-bước-cuối) · [8. Checklist](#8-checklist-tổng-hợp)

---

## 1. Thiết lập môi trường local

### 1.1 Yêu cầu

- PHP >= 7.4 (khuyến nghị 8.3+) với ext: `mysqli` (hoặc `pgsql`/`sqlite3`), `xml`, `pcre`, `zip`, `dom`, `curl`, `gettext`, `gd` hoặc `imagick`, `mbstring`.
- Node.js >= 18 + npm (chỉ cần khi **vendor refresh** theme/admin — mục 1.4).
- Git >= 2.30. **Không cần** hỗ trợ submodule (dự án đã bỏ submodule — Phương án D).
- DB: MySQL 5.7+/MariaDB 10.2+, hoặc PostgreSQL 11+, hoặc SQLite.

### 1.2 Bootstrap repo

```bash
git clone https://github.com/honggiang13/Vvveb.git
cd Vvveb
git checkout vc-dev
composer install                            # chỉ tạo vendor/autoload.php, an toàn
```

**Không có bước submodule.** Toàn bộ theme, admin template và plugin vendor nằm sẵn trong repo.

⚠️ **Giai đoạn chuyển tiếp:** nếu `config/db.php` render lỗi hoặc `public/themes/landing` còn rỗng ⇒ migration **Phương án D** (`architecture.md` §2.6) chưa được chạy. Chạy checklist đó trước khi tiếp tục.

Kiểm tra nhanh môi trường:

```powershell
(Get-ChildItem public\themes\landing -Force | Measure-Object).Count   # > 0 ⇒ OK
(Get-ChildItem public\admin\default  -Force | Measure-Object).Count   # > 0 ⇒ OK
Test-Path config/db.php                                               # True sau khi install
Test-Path .gitmodules                                                 # False sau migration
```

### 1.3 Chạy local

**Cách A — PHP built-in server (nhanh nhất):**

```bash
php -S 127.0.0.1:8080 -t public          # doc root PHẢI là public/
```

Mở `http://127.0.0.1:8080/install/` cho lần đầu (tạo `config/db.php`).
⚠️ PHP built-in server không xử lý `.htaccess` ⇒ page cache và một số rewrite rule không hoạt động; dùng Cách B/C để test đầy đủ.

**Cách B — Apache:** trỏ DocumentRoot vào `public/`, bật `mod_rewrite`, `AllowOverride All`. Xem `apache-vvveb.conf`.

**Cách C — Nginx:** dùng `nginx-live.conf` (production) hoặc `nginx-live-nocache.conf` (dev), sửa `root` trỏ vào `public/`.

**Cách D — Docker:**

```bash
docker compose up -d          # http://localhost:8080  (db: mysql, user/pass: vvveb)
# hoặc: docker compose -f docker-compose-apache.yaml up -d
```

### 1.4 Vendor refresh theme / admin assets (KHÔNG phải bước thường ngày)

Vì dự án đã bỏ submodule (Phương án D), nội dung theme/admin **nằm sẵn trong repo** — bình thường bạn không cần build gì cả. Chỉ khi cần nâng cấp giao diện:

1. Build ở **repo phụ** (không phải trong core):

```bash
# Thư mục local khớp tên repo: ../vvveb-landing (xem architecture.md §2.1)
cd ../vvveb-landing            && npm install && npm run gulp
# Chỉ khi sections/blocks/styles chưa có: git ls-files --error-unmatch sections/sections.js blocks/blocks.js styles/styles.js
npm run gulp sections && npm run gulp blocks && npm run gulp styles
cd ../vvveb-admin-template     && npm install && npm run gulp
```

1. **Mirror có kiểm soát vào core** (`robocopy /MIR`) — chi tiết đầy đủ tại `architecture.md` §2.4. 
   > ⚠️ **Quan trọng — `.gitignore` của core:** dòng `.gitignore:9` là `public/themes/*/*.html` (HTML cấp gốc bị ignore). Sau khi mirror, phải un-ignore bằng `!public/themes/*/*.html` rồi `git add` lại, vì `system/extensions/themes.php:70` chỉ phát hiện theme dựa trên `public/themes/*/index.html`. Dùng `git ls-files public/themes/landing/index.html` để xác minh.

⚠️ **Bắt buộc** kiểm tra artifact `sections/blocks/styles` trước khi chạy chúng: `git -C <theme-repo> ls-files --error-unmatch sections/sections.js`. Ở `a801d3a` của landing chúng đã được track, nhưng không phải theme nào cũng vậy (vd `vvveb-blog-default` có thể chỉ có `styles/styles.js` hoặc không có `vvveb.js`). `admin/controller/editor/editor.php::loadThemeAssets()` glob `$themeFolder/{inputs,components,blocks,sections,styles}/*.js` để nạp vào editor — thiếu chúng ⇒ editor mất danh sách section/block/style mà không báo lỗi rõ ràng.

`./build.sh` là script **tạo release** của upstream, không phải bước build thường ngày: nó build mọi `public/*/*` có `gulpfile.js` rồi **xóa sạch cache/log/backup** và tạo `vvveb.zip`. Chỉ chạy khi chủ động tạo bản phát hành (cần Git Bash/WSL).

### 1.5 Bật/tắt chế độ dev

`env.php` (đừng commit thay đổi chỉ để dev nếu có thể):

```php
define('SQL_CHECK', true);    // dev: recompile model khi .sql đổi — TẮT ở production
define('PAGE_CACHE', true);   // dev nên đặt false để thấy thay đổi ngay
defined('DEBUG') || define('DEBUG', true);       // dev only
defined('VTPL_DEBUG') || define('VTPL_DEBUG', true);  // dev only, log compile vtpl
```

Sau khi sửa template/theme mà không thấy thay đổi → clear cache:

```bash
php -r "array_map('unlink', glob('storage/compiled-templates/*'));"
php -r "array_map('unlink', glob('storage/cache/*'));"
php -r "array_map('unlink', glob('public/page-cache/*'));"
```

### 1.6 Install / CLI không cần browser

```bash
# MySQL
php cli.php install engine=mysqli host=127.0.0.1 user=root password= database=vvveb \
  admin[email]=admin@example.com admin[username]=admin admin[password]=admin

# SQLite (đơn giản nhất cho dev)
php cli.php install engine=sqlite \
  admin[email]=admin@example.com admin[username]=admin admin[password]=admin

# Kiểm tra hệ thống: kích hoạt plugin
php cli.php admin module=plugin/plugins action=checkPluginAndActivate plugin=contact-form

# Cron & kiểm tra frontend không cần browser
php cli.php app module=cron
php cli.php app request_uri=/hello-world
```

---

## 2. Workflow phát triển tiêu chuẩn

```
[1] PLANNING      → tạo vc-dev/{features|issues}/<name>.md  (BẮT BUỘC, xem §4)
[2] BRANCHING     → git checkout vc-dev && git pull && git checkout -b feature/<name>
[3] CODING        → tuân thủ architecture.md §4.2 (thứ tự ưu tiên mở rộng)
[4] TESTING       → manual + cache clear + checklist §8
[5] PR → vc-dev   → PR template, điền Handoff Summary
[6] REVIEW        → reviewer ĐỘC LẬP (context sạch) review; ghi kết quả vào vc-dev/pr-review/ (§6.3)
[7] MERGE         → chỉ merge khi có review record với verdict APPROVED hoặc APPROVED WITH FOLLOW-UPS,
                    và head SHA khớp commit đã review; xóa branch, Plan → MERGED
[8] UPDATE DOCS   → cập nhật Plan tasklist + kiến thức vào file instructions nếu có thay đổi cấu trúc
```

### 2.1 Branch naming

| Loại | Format | Ví dụ |
|---|---|---|
| Tính năng | `feature/<kebab-name>` | `feature/loyalty-points` |
| Fix bug | `fix/<issue-id>-<slug>` | `fix/12-checkout-currency` |
| Nâng cấp/nợ kỹ thuật | `chore/<slug>` | `chore/vendor-refresh-landing` |
| Sync upstream | `sync/upstream-<date>` | `sync/upstream-2026-10-21` |
| Docs | `docs/<slug>` | `docs/vc-dev-bootstrap` |

### 2.2 Commit message

```
<type>(<scope>): <mô tả ngắn, mệnh lệnh, không hoa đầu>

<body: vì sao, không phải cái gì>

Refs: vc-dev/features/<name>.md#<task-id>
```

`type`: `feat` | `fix` | `docs` | `chore` | `refactor` | `perf` | `test` | `sync`.
`scope` gợi ý: `plugin/<slug>`, `theme/<slug>`, `config`, `core`, `admin`, `app`, `builder`.

Ví dụ:
```
feat(plugin/loyalty): tích điểm theo đơn hàng

Hook vào checkout::add:after để cộng điểm; lưu bảng riêng loyalty_points,
không sửa core. Dùng Event::on trong plugin.php.

Refs: vc-dev/features/loyalty-points.md#t3
```

### 2.3 Merge vào `vc-dev`

- **Branch Protection Rule:** **TUYỆT ĐỐI KHÔNG push trực tiếp** lên `vc-dev` và `vc-prod`. **BẮT BUỘC** tạo feature/fix branch → commit → push branch → tạo PR → independent review → merge qua PR. GitHub branch protection rules enforced.

- Chỉ **PR** (không push trực tiếp) vào `vc-dev`.
- Yêu cầu: CI/kiểm tra thủ công pass, Plan file đã cập nhật đủ `COMPLETED` + `## Handoff Summary`.
- **Yêu cầu bắt buộc:** đã có **review record độc lập** tại `vc-dev/pr-review/pr-<number>-<slug>.md` với verdict `APPROVED` hoặc `APPROVED WITH FOLLOW-UPS` (quy trình §6.3). Không có file này ⇒ **không merge**.
- Prefer **squash merge** cho feature branch nhỏ; **merge commit** khi muốn giữ lịch sử nhiều bước.
- Sau merge: xóa branch, cập nhật Plan sang trạng thái `MERGED`, cập nhật trạng thái trong file review record.

### 2.4 Sync upstream (định kỳ hoặc khi có bản vá bảo mật)

```bash
git remote add upstream https://github.com/givanz/Vvveb.git   # 1 lần
git fetch upstream --tags
git checkout master && git merge --ff-only upstream/master
git push origin master
git checkout vc-dev && git merge master
# → giải quyết conflict: ưu tiên upstream, re-apply patch có marker "vc-dev:patch"
git push origin vc-dev
```

Ghi lại kết quả sync vào `vc-dev/issues/sync-upstream-<date>.md`.

---

## 3. Hướng dẫn mở rộng (theo thứ tự ưu tiên ít can thiệp core)

### 3.1 Viết Plugin

**Khi nào:** thêm khả năng mới có vòng đời riêng, có UI/config riêng (thanh toán, vận chuyển, import, SEO, form…).

**Cấu trúc:**

```
plugins/my-plugin/
├── plugin.php                      # BẮT BUỘC: metadata (doc-comment) + hooks
├── README.md
├── admin/
│   ├── controller/settings.php     # module: plugins/my-plugin/settings
│   └── template/settings.tpl
├── app/
│   ├── controller/index.php
│   └── template/index.tpl
├── public/                         # copy → public/plugins/my-plugin khi activate
│   └── css/my-plugin.css
└── sql/{mysqli,pgsql,sqlite}/      # model SQL (tùy chọn)
```

**`plugin.php` mẫu:**

```php
<?php
/**
 * Name: My Plugin
 * Description: Mô tả ngắn gọn.
 * Version: 1.0.0
 * Author: Your Name
 * Category: ecommerce
 * Settings: /admin/index.php?module=plugins/my-plugin/settings
 */

namespace Vvveb\Plugins\MyPlugin;

use Vvveb\System\Event;

// Lưu ý: plugin.php được `include` bên trong hàm loadPlugins()
// ⇒ biến $site_id và $pluginName có sẵn trong scope.

Event::on('Vvveb\Controller\Base', 'init-menu',
    __FILE__,
    function (&$menu) {
        $menu[] = ['name' => 'My Plugin', 'url' => adminPath() . 'index.php?module=plugins/my-plugin/settings'];
        return [$menu];       // ⚠️ LUÔN return mảng tham số
    }, 50);
```

**Quy tắc plugin bắt buộc:**

1. Metadata dùng đúng format doc-comment `Key: value` (không có dấu `:` thừa trong key).
2. Callback event **phải return mảng tham số** (dạng `[$arg1, $arg2]`), nếu không core sẽ giữ giá trị cũ.
3. Callback phải là closure **không phụ thuộc biến ngoài** hoặc dùng `use` tường minh.
4. Trạng thái plugin ghi vào `config/plugins.php` — **không** tự ý sửa qua tay; dùng `Plugins::activate/deactivate`.
5. `plugin.php` không được gọi `include` file khác trước khi core autoloader sẵn sàng.
6. Bắt đầu file bằng `if (! defined('V_VERSION')) { return; }` để chống truy cập trực tiếp qua HTTP (khi plugin nằm trong webroot của một số setup).
7. Cho SQL: đặt `.sql` trong `sql/<engine>/` và class kết thúc bằng `SQL` để core compile thành model (`SQL_CHECK`).
8. Test tối thiểu: activate/deactivate qua CLI, load frontend, load admin (không safemode).

```bash
php cli.php admin module=plugin/plugins action=checkPluginAndActivate plugin=my-plugin
php cli.php admin module=plugin/plugins action=deactivate plugin=my-plugin
```

### 3.2 Thêm Config

**Nguyên tắc:** config là **file mới** hoặc **key mới**; không sửa cấu trúc file config core hiện có.

| Tôi cần… | Làm ở đâu |
|---|---|
| Feature flag toàn cục | `env.php` (chỉ khi thực sự cần, ghi trong Plan) |
| Config riêng của plugin | `Setting::setSetting('my-plugin', 'key', $value)` (DB, theo site) |
| Route mới | Route trong `plugin.php` qua event `Vvveb\System\Routes::init`, hoặc `config/app-routes.php` |
| Site mới / đổi theme site | `config/sites.php` (hoặc `Sites::saveSite()`) |
| Menu admin | event `Vvveb\Controller\Base::init-menu` |
| Custom post/product type | event `Vvveb\postTypes::customPost\|customProduct` |

**Đọc/ghi config đúng cách:**

```php
use function Vvveb\getConfig;
use function Vvveb\setConfig;

$value = getConfig('plugins.1.my-plugin.status', 'inactive');
setConfig('plugins.1.my-plugin.status', 'active');   // ghi file config — cẩn thận quyền ghi
```

⚠️ `setConfig()` ghi vào `config/*.php` bằng `var_export` ⇒ file phải writable; mất quyền ghi sẽ làm hỏng luồng activate plugin. Trên production nên dùng `Setting` (DB) cho giá trị thay đổi thường xuyên.

⚠️ **Không** commit bất kỳ giá trị bí mật nào mới vào `config/`. Với secret của plugin, dùng `Setting` + mã hóa, hoặc tham chiếu biến môi trường.

### 3.3 Thêm Theme / Override template

**Theme mới:** tạo `public/themes/<slug>/` với tối thiểu `index.html`, `theme.json`, `gulpfile.js`, `scss/`. Gán cho site ở `config/sites.php`. Đây là theme **của ta** ⇒ sửa trực tiếp được. **Không** sửa trực tiếp `landing` / `blog-default` (theme vendor) — muốn đổi chúng thì vendor refresh (`architecture.md` §2.4) hoặc tạo theme riêng kế thừa.

**Override template không fork theme:** dùng file `.tpl` — `admin/controller/base.php` và `app/controller/*` chọn `.tpl` tương ứng; thêm file lệnh `.tpl` bổ sung để chèn/sửa node HTML của theme mà không cần sửa HTML gốc. Cách này giữ theme nguyên bản để còn cập nhật từ upstream.

**Quy tắc vtpl:**
- Selector trong `.tpl` trỏ vào `data-v-*` của HTML theme; đổi HTML theme ⇒ selector có thể vỡ.
- Sau khi sửa `.tpl`/HTML: xóa `storage/compiled-templates/*` (hoặc bật `VTPL_DEBUG` để thấy lỗi compile).
- Cache compiled templates đặt tên `{app}_{site}_{theme}_{template}` ⇒ đổi theme/site sẽ tạo file cache mới.

---

## 4. Quy trình Planning, Tasklist & Multi-Agent Handoff

> **Đây là quy trình BẮT BUỘC.** Không được bắt đầu code trước khi có file Plan.

### 4.1 Tạo file Plan (bước 0, trước mọi tác vụ)

| Loại công việc | Vị trí file |
|---|---|
| Tính năng mới | `vc-dev/features/<feature-name>.md` |
| Fix bug / cải tiến / nợ kỹ thuật | `vc-dev/issues/<issue-id-or-name>.md` |

Quy ước đặt tên: kebab-case, ngắn, không dấu. Ví dụ: `vc-dev/features/loyalty-points.md`, `vc-dev/issues/12-checkout-currency.md`.

### 4.2 Template file Plan (copy nguyên khối)

```markdown
# <Tên feature/issue>

- **ID/Ticket:** <link hoặc id>
- **Branch:** `feature/<name>`
- **Owner hiện tại:** <human | agent/model name>
- **Trạng thái tổng:** `PLANNING | IN_PROGRESS | REVIEW | MERGED | BLOCKED`
- **Tạo ngày:** YYYY-MM-DD · **Cập nhật:** YYYY-MM-DD

## Objective
Mục tiêu cần đạt, kèm tiêu chí đo lường được (Definition of Done).
1. …
2. …

## Scope & Impact Analysis
- **Module/file ảnh hưởng:** …
- **Mức can thiệp core (architecture.md §4.2):** mức 1–6 — lý do chọn
- **Giải pháp bảo vệ code core:** plugin / theme / config / event hook / create-only / patch có marker
- **Rủi ro & giảm thiểu:** …
- **Ảnh hưởng khi sync upstream:** không | có (mô tả)
- **Ngoài phạm vi (Out of scope):** …

## Step-by-Step Tasklist
- [ ] PENDING: T1 — …
- [ ] PENDING: T2 — …
- [ ] PENDING: T3 — …

## Test Plan
- [ ] PENDING: Manual — …
- [ ] PENDING: CLI/automation — …

## Handoff Summary
(chỉ điền khi kết thúc 1 Step/Phase — xem §4.5)
```

### 4.3 Cập nhật trạng thái tasklist (trong khi làm)

Trạng thái hợp lệ và cách viết:

```markdown
- [ ] PENDING: T1 — chưa bắt đầu
- [ ] IN_PROGRESS: T2 — đang làm (owner: <agent>), bắt đầu YYYY-MM-DD
- [x] COMPLETED: T3 — xong lúc YYYY-MM-DD, commit abc1234
- [ ] BLOCKED: T4 — chờ <điều kiện>, cần <ai> xử lý
```

Quy tắc:
1. Đổi `PENDING` → `IN_PROGRESS` **trước khi** bắt đầu sub-task.
2. Đổi sang `- [x] COMPLETED` **ngay khi** xong, kèm commit hash nếu có.
3. Task bị chặn ghi `BLOCKED` + lý do cụ thể; **không** để task treo ở `IN_PROGRESS` qua nhiều phiên làm việc.
4. Mỗi lần kết thúc phiên làm việc: cập nhật `**Cập nhật:** YYYY-MM-DD` và Handoff Summary.
5. Việc phát sinh ngoài plan: thêm task mới **cuối** tasklist, không xóa task cũ (giữ lịch sử).

### 4.4 Khi nào tạo issue mới thay vì thêm vào plan hiện tại?

Tạo file mới khi: kết quả task vượt > 1 ngày công hoặc chạm module khác `Scope` ban đầu; hoặc phát hiện bug độc lập không thuộc mục tiêu (ghi lại `vc-dev/issues/<name>.md` rồi tiếp tục task hiện tại).

### 4.5 Multi-Agent Coordination & Handoff

Công việc có thể được nối tiếp bởi nhiều agent/developer. Khi **hoàn thành 1 Step/Phase** (hoặc kết thúc phiên làm việc), agent phụ trách **bắt buộc** ghi `## Handoff Summary` ở cuối file Plan:

```markdown
## Handoff Summary

### Status Current Phase
- Phase: <tên phase, ví dụ "Phase 1 — Đăng ký plugin & hook checkout">
- Đã xong: T1, T2, T3 (COMPLETED)
- Còn lại: T4 (BLOCKED — chờ quyền ghi `config/`), T5 (PENDING)
- Commit cuối: abc1234 trên `feature/<name>`
- Test đã chạy: <lệnh + kết quả>

### Key Decisions & Findings
1. <quyết định kiến trúc đã chốt> — lý do, và các phương án đã loại.
2. <phát hiện quan trọng về hệ thống> (ví dụ: "hook init-menu chạy trước khi plugin load trong admin ⇒ phải đăng ký ở plugin.php, không phải trong controller").
3. <điều KHÔNG nên làm> và lý do (để agent sau không đi sai hướng).

### Context for Next Agent
- **File đã tạo/sửa:** `plugins/my-plugin/plugin.php` (mới), `config/plugins.php` (thay đổi bởi core khi activate), …
- **File CHƯA nên sửa:** `system/event.php`, thư mục vendor (`public/themes/landing`, `public/admin/default`, `public/themes/blog-default`, plugin vendor).
- **Biến môi trường/cấu hình:** `env.php: DEBUG=true` (dev), `config/plugins.php` site 1.
- **Lệnh build/test cần chạy:**
  - `php -r "array_map('unlink', glob('storage/compiled-templates/*'));"`
  - `php cli.php admin module=plugin/plugins action=checkPluginAndActivate plugin=my-plugin`
- **Trạng thái môi trường/vendor:** <ví dụ: "`public/admin/default` còn rỗng ⇒ migration Phương án D chưa chạy, không test được admin UI"> · <ví dụ: "landing vendor ở commit a801d3a, ngày 2026-10-21">
- **Cạm bẫy đã gặp:** <mô tả lỗi + cách xử lý>

### Next Action Items
1. <việc cụ thể ngay khi tiếp nhận — nêu rõ file/lệnh>
2. <việc tiếp theo>
3. <nếu bị chặn: cần ai/quyền gì>
```

**Quy tắc handoff:**
- Handoff Summary là **bắt buộc**, kể cả khi phase kết thúc bình thường.
- Agent tiếp nhận **phải đọc Header + `## Handoff Summary`** trước khi đọc toàn bộ plan.
- Không xóa Handoff Summary cũ; nếu có nhiều phase, ghi nối tiếp `## Handoff Summary` mới bên dưới với tiêu đề `### Handoff #2 — YYYY-MM-DD`.
- Nếu agent bị chặn hoàn toàn: đặt **Trạng thái tổng:** `BLOCKED` + Handoff Summary với `Next Action Items` nêu rõ người cần liên hệ.

---

## 5. Testing

Hiện repo **không có test framework** (không có `tests/`, `phpunit.xml`, `.github/workflows`). Vì vậy mọi PR phải kèm bằng chứng test thủ công/CLI tự động hóa.

### 5.1 Test tối thiểu bắt buộc cho mọi PR

1. **PHP syntax:** `php -l <file-đã-sửa>` (mọi file `.php` thay đổi).
2. **Clear cache** rồi load lại trang ảnh hưởng.
3. **Frontend smoke:** trang chủ + trang bị ảnh hưởng render không lỗi (DEBUG=true quan sát).
4. **Admin smoke:** `/{admin-path}/` login, mở module bị ảnh hưởng (nếu PR chạm admin).
5. **Plugin lifecycle** (nếu PR là plugin): activate → verify → deactivate qua CLI.

### 5.2 Kịch bản test theo loại thay đổi

| Thay đổi | Test cần chạy |
|---|---|
| Plugin mới | activate/deactivate CLI; frontend route của plugin; admin settings page; không safemode |
| Hook/event mới | Trigger trước/sau, priority, `return null` không làm mất tham số |
| Config mới | Quyền ghi file; rollback khi `setConfig()` fail |
| Theme/`.tpl` | Xóa compiled templates; render `-edit` mode (editor) và mode thường |
| Patch core (mức 6) | So sánh hành vi trước/sau + ghi marker `vc-dev:patch` + test lại sau khi rebase |
| Vendor refresh | Ghi commit nguồn vào bảng `architecture.md` §2.5; verify không còn file mồ côi (mirror đúng); smoke test toàn bộ UI liên quan; verify `sections/`, `blocks/`, `styles/` list còn tồn tại |

### 5.3 Ghi lại bằng chứng

Trong file Plan, mục `## Test Plan`, đổi sang COMPLETED kèm **lệnh đã chạy + kết quả quan sát**. Đây là dữ liệu handoff quan trọng.

---

## 6. Pull Request & Review

### 6.1 PR template (dán vào PR description)

```markdown
## Mục tiêu
<1-3 dòng, link tới vc-dev/{features|issues}/<name>.md>

## Mức can thiệp core
mức <1-6> — <plugin|theme|config|hook|create-only|patch core>

## Thay đổi
- <file/folder chính>

## Test đã chạy
- [ ] `php -l` các file thay đổi
- [ ] Clear compiled templates + cache
- [ ] Frontend smoke
- [ ] Admin smoke (nếu có)
- [ ] Plugin activate/deactivate (nếu có)
- Kết quả: <mô tả>

## Ảnh hưởng upstream sync
không | có — <mô tả + kế hoạch rebase>

## Plan file
vc-dev/features/<name>.md — tasklist COMPLETED + Handoff Summary ✔
```

### 6.2 Checklist reviewer

- [ ] Có file Plan tương ứng và đã `COMPLETED` + `## Handoff Summary`.
- [ ] Không sửa file thuộc danh sách cấm (`architecture.md` §4.3, `agent-guide.md` §2).
- [ ] Patch core (nếu có) có marker `vc-dev:patch` + lý do + ghi trong Plan `Scope & Impact Analysis`.
- [ ] Không commit secret (`config/db.php`, key trong `config/app.php`), không commit artifact (`*.zip`, `node_modules`, file build).
- [ ] Không sửa trực tiếp file trong thư mục vendor (theme/admin template/plugin vendor) — thay đổi vendor phải qua quy trình `architecture.md` §2.4.
- [ ] Nếu là vendor refresh: bảng `architecture.md` §2.5 đã cập nhật commit nguồn; `.git/`/`node_modules/` của repo phụ không bị mang vào core.
- [ ] Coding style khớp file xung quanh (tab, namespace, `System\Event`).
- [ ] Đã ghi ảnh hưởng tới upstream sync.
- [ ] Tài liệu trong `vc-dev/instructions/` được cập nhật nếu thay đổi cấu trúc/quy trình.
- [ ] Đã tạo `vc-dev/pr-review/pr-<number>-<slug>.md`; verdict + commit SHA khớp `headRefOid` PR; đã cập nhật `vc-dev/pr-review/README.md`.

### 6.3 Independent PR review — BẮT BUỘC trước khi merge

> Áp dụng cho **mọi PR** vào `vc-dev`, kể cả PR chỉ sửa docs/plan. Mục tiêu: chống "tự review bài mình viết" và bảo toàn bằng chứng review trong repo (vì repo không có CI).

**Ai review**

- Reviewer phải có **context độc lập** với người viết PR: session/agent riêng, không dùng lại context đã tạo ra diff.
- Ưu tiên model/agent mà user chỉ định (ví dụ Kilocode). Nếu provider/model đó **không có trong cấu hình** ⇒ dùng subagent `general` (context sạch) hoặc model khả dụng khác, và **ghi rõ lý do thay thế** trong file review.
- Reviewer không được là tác giả PR. GitHub chặn `gh pr review --approve` trên PR của chính tài khoản mình ⇒ khi trùng tài khoản, **file review trong `vc-dev/pr-review/` là bằng chứng chính thức** thay cho approval trên GitHub.
- **Thực tế repo này (một tài khoản GitHub):** `vc-dev` có branch protection yêu cầu 1 approval và `enforce_admins=false`. Vì chỉ có tài khoản `honggiang13`, mọi PR luôn ở trạng thái `REVIEW_REQUIRED` và **không thể** có approval hợp lệ. Quy trình merge vì vậy là: có review record hợp lệ → `gh pr merge <n> --admin`. Đây là **kỷ luật nội bộ + bằng chứng trong repo**, GitHub không cưỡng chế. Muốn cưỡng chế thật ⇒ thêm collaborator thứ hai (hoặc GitHub App/bot reviewer).

**Reviewer phải làm gì**

1. Đọc PR description + diff thật (`gh pr view <n> --json title,body,files`, `gh pr diff <n>`).
2. **Xác minh claim bằng lệnh thật** trong repo — không tin docs/diff một chiều. Ví dụ: `git ls-files`, `git check-ignore -v --no-index`, `git -C <repo> rev-parse HEAD`, `gh repo view`, đọc file nguồn.
3. Đối chiếu checklist §6.2 và tính đúng đắn kỹ thuật (thứ tự lệnh, đường dẫn, khả năng mất dữ liệu).
4. Phân loại finding theo mức: `BLOCKER` / `MAJOR` / `MINOR` / `NIT`, kèm vị trí (file + dòng/quote) và cách sửa đề xuất.
5. Nêu rõ **claim đã xác minh đúng** và **claim không xác minh được** (kèm lý do).
6. Kết luận: `APPROVED` | `APPROVED WITH FOLLOW-UPS` | `CHANGES REQUIRED` | `HOLD`.

**Xử lý sau review — ai được đổi verdict**

- **Tác giả** chỉ được: tự xác minh lại từng finding, sửa code/docs, trả lời từng finding (kể cả phản biện nếu reviewer sai — ghi lý do vào file review), và push commit fix. **Tác giả KHÔNG được tự đổi `Verdict cuối`.**
- **Reviewer** là người duy nhất đổi `Verdict cuối`, sau khi **re-review phần delta** (diff từ `Commit review` đến head mới) và chạy lại lệnh xác minh cho các finding đã sửa.
- Mỗi vòng re-review ghi thêm khối `## Re-review <n>` với `Head SHA` mới + kết luận từng finding (`FIXED` / `STILL OPEN` / `REJECTED — chấp nhận lý do`).
- `BLOCKER` và `MAJOR` phải `FIXED` trước khi merge.
- `MINOR`/`NIT` để follow-up chỉ được chấp nhận nếu **đã tạo file theo dõi** (`vc-dev/issues/<slug>.md` hoặc task trong plan hiện tại) và **link file đó trong review record**. Không có link ⇒ coi như còn OPEN ⇒ không merge.
- Nếu reviewer yêu cầu thay đổi nhưng tác giả không đồng ý ⇒ ghi cả hai quan điểm, đặt `HOLD`, escalate cho human quyết định.

**Điều kiện merge (ràng buộc theo commit)**

- Verdict cuối phải là `APPROVED` hoặc `APPROVED WITH FOLLOW-UPS`.
- `headRefOid` của PR lúc merge **phải bằng** `Commit review` (hoặc `Re-review commit` gần nhất).
- Nếu có commit mới sau commit đã review ⇒ **bắt buộc re-review** phần delta. Push thêm commit rồi merge là vi phạm.
- Kiểm tra trước khi merge:
  ```powershell
  gh pr view <n> --json headRefOid --jq .headRefOid   # so với 'Commit review' trong file
  gh pr view <n> --json reviewDecision --jq .reviewDecision
  ```

**File review (bắt buộc)**

- Đường dẫn: `vc-dev/pr-review/pr-<number>-<slug>.md` (ví dụ `pr-2-migrate-plan.md`).
- Mỗi file phải có: reviewer + ngày, branch/base, **commit đã review (head SHA)**, verdict, findings (severity + vị trí + fix + trạng thái), claim đã xác minh, claim không xác minh được, các khối re-review, quyết định cuối.
- File review được commit vào `vc-dev` (hoặc nhánh PR) và **phải tồn tại trước khi merge**; verdict cuối do reviewer commit.
- Cập nhật `vc-dev/pr-review/README.md` (bảng index) khi thêm file mới hoặc đổi verdict.

**Checklist hoàn tất review (trước khi merge)**

- [ ] File `vc-dev/pr-review/pr-<number>-<slug>.md` tồn tại trên nhánh/`vc-dev`.
- [ ] `Commit review` khớp `gh pr view <n> --json headRefOid` (hoặc đã có re-review cho commit mới hơn).
- [ ] Verdict cuối `APPROVED` / `APPROVED WITH FOLLOW-UPS`; **không** còn `BLOCKER`/`MAJOR` OPEN.
- [ ] Mọi follow-up `MINOR`/`NIT` có link file theo dõi.
- [ ] Index `vc-dev/pr-review/README.md` đã cập nhật.

**Mẫu tối thiểu:**

```markdown
# PR #<n> — `<branch>`

- **Reviewer:** <agent/model/session độc lập> (ghi rõ nếu thay thế model được chỉ định)
- **Ngày:** YYYY-MM-DD
- **Base:** `vc-dev` · **Commit review:** `<head SHA>`
- **Verdict ban đầu:** APPROVED | APPROVED WITH FOLLOW-UPS | CHANGES REQUIRED | HOLD
- **Verdict cuối:** <do reviewer điền sau re-review>

## Findings
### 1. <SEVERITY> — <tóm tắt>
- **Vị trí:** `<file>:<line>` hoặc quote
- **Vấn đề:** <cái gì sai, tại sao>
- **Bằng chứng:** `<lệnh + output>`
- **Fix:** <cách sửa>
- **Trạng thái:** <FIXED (commit `<hash>`) | OPEN | REJECTED + lý do>

## Re-review 1
- **Head SHA:** `<hash>`
- **Kết quả:** <từng finding: FIXED / STILL OPEN / REJECTED — chấp nhận>

## Claim đã xác minh đúng
- <claim> — <lệnh xác minh>

## Claim không xác minh được
- <claim> — lý do

## Quyết định
<APPROVED / HOLD + lý do ngắn>
```

**Quy trình bằng lệnh (PowerShell):**

```powershell
# 1. Thu thập dữ liệu PR
gh pr view <n> --json title,body,files,additions,deletions
gh pr diff <n>
gh pr view <n> --json commits --jq '.commits[].oid'

# 2. Xác minh claim (ví dụ)
git ls-files -s | Select-String "^160000"
git check-ignore -v --no-index public/themes/landing/index.html
git -C ..\vvveb-landing rev-parse --short HEAD
gh repo view honggiang13/vvveb-blog-default --json name

# 3. Ghi file review rồi commit
#    vc-dev/pr-review/pr-<n>-<slug>.md
git add vc-dev/pr-review/
git commit -m "docs(vc-dev): review PR #<n> — <verdict>"
```

---

## 7. Documentation update (bước cuối)

Cập nhật tài liệu khi PR thay đổi **cấu trúc, quy trình, hoặc điểm mở rộng**:

| Thay đổi | Cập nhật file |
|---|---|
| Thêm/bỏ thành phần kiến trúc, đổi repo phụ | `vc-dev/instructions/architecture.md` §1–§3, §5 |
| Vendor refresh (nâng cấp theme/admin/plugin) | `vc-dev/instructions/architecture.md` §2.5 (bảng theo dõi phiên bản) — **bắt buộc** |
| Thêm script/lệnh mới, đổi build | `vc-dev/instructions/skills.md` |
| Thêm quy ước/ràng buộc cho agent | `vc-dev/instructions/agent-guide.md` |
| Đổi quy trình dev/PR/test | `vc-dev/instructions/development.md` |
| Kết quả review bất kỳ PR nào | `vc-dev/pr-review/pr-<number>-<slug>.md` + index `vc-dev/pr-review/README.md` — **bắt buộc** |

Đây là bước **bắt buộc** trong workflow — PR không cập nhật docs sẽ bị yêu cầu bổ sung.

---

## 8. Checklist tổng hợp

**Trước khi code:**
- [ ] Đã `git pull` trên `vc-dev`, đã tạo branch đúng naming.
- [ ] Đã tạo file Plan ở đúng thư mục với đủ 3 mục `Objective`, `Scope & Impact Analysis`, `Step-by-Step Tasklist`.
- [ ] Đã xác định mức can thiệp core (1–6) và ghi vào Plan.
- [ ] Thư mục vendor không rỗng nếu task chạm theme/admin/plugin vendor (`(Get-ChildItem public\themes\landing | Measure-Object).Count` > 0).

**Trong khi code:**
- [ ] Tasklist cập nhật trạng thái thời gian thực (`IN_PROGRESS` → `COMPLETED`).
- [ ] Commit message theo format §2.2, có `Refs:` tới plan.
- [ ] Không đụng danh sách file cấm.

**Trước khi mở PR:**
- [ ] Test theo §5 đã chạy, bằng chứng ghi vào Plan.
- [ ] `## Handoff Summary` đã điền.
- [ ] Docs cập nhật nếu cần (§7).

**Sau khi mở PR (bắt buộc):**
- [ ] Đã chạy review độc lập (§6.3) với reviewer/session khác tác giả.
- [ ] Đã ghi `vc-dev/pr-review/pr-<number>-<slug>.md` + cập nhật `vc-dev/pr-review/README.md`.
- [ ] Mọi finding `BLOCKER`/`MAJOR` đã fix và verdict cuối là `APPROVED` / `APPROVED WITH FOLLOW-UPS`.
- [ ] **Sync latest từ nhánh target PR** (bắt buộc - đảm bảo không xung đột file cấu hình/index).
- [ ] Chỉ merge khi có review record; sau merge cập nhật Plan → `MERGED`.

