# PR #38 — `chore/production-external-db`

- **Reviewer:** independent session (Kilo — `kilo-auto/free`), context sạch, không phải tác giả PR
- **Ngày:** 2026-10-22
- **Base:** `vc-dev` · **Commit review:** `44fd69b9116565d1700b58c4d788f7b06e185e20`
- **PR:** #38 — "chore(docker): production dùng MySQL ngoài do Coolify quản lý" (`headRefOid` khớp `Commit review`)
- **Commit trong PR:** `1ea1831`, `37c76e4`, `44fd69b`
- **Verdict ban đầu:** APPROVED WITH FOLLOW-UPS
- **Verdict cuối:** APPROVED WITH FOLLOW-UPS

## Tóm tắt

PR bỏ service `db` (mysql:8.0) + volume `vvveb_db_data` + `depends_on` khỏi `docker-compose.production.yaml`,
chuyển production sang MySQL ngoài do Coolify quản lý; giữ lại bản cấu hình cũ ở
`docker-compose.production.full.yaml` làm phương án rollback; viết lại vòng chờ DB trong
`docker-entrypoint.sh` theo `connect_errno` và thêm `fresh-install.sh`.

Diff **không sửa code PHP core** (mức 3 — config), không đụng danh sách file cấm
(`agent-guide.md` §2), không chứa secret, không mang file DB/wip của repo.

Không có `BLOCKER`/`MAJOR`. Có 5 `MINOR` + 3 `NIT` về cấu hình/vận hành và độ chính xác của tài liệu,
đều không làm hỏng deploy khi đã set đúng biến Coolify.

## Findings

### 1. MINOR — Thiếu `DB_HOST`/`DB_PASSWORD` không chặn deploy, site lên trang installer nhưng báo "healthy"

- **Vị trí:** `docker-compose.production.yaml:32` (`DB_HOST: ${DB_HOST}`), `docker-compose.production.yaml:36` (`DB_PASSWORD: ${DB_PASSWORD}`)
- **Vấn đề:** `${DB_HOST}` không có default ⇒ compose chỉ in warning rồi điền chuỗi rỗng. Khi `DB_HOST=""`,
  `docker-entrypoint.sh:24` (`if [ -n "$DB_HOST" ]`) bỏ qua hẳn vòng chờ DB (không in cảnh báo), app vẫn boot,
  `/` trả HTTP 200 (trang installer) ⇒ healthcheck `curl -f http://localhost:80/` (`docker-compose.production.yaml:51`)
  pass ⇒ Coolify báo deploy thành công dù site không có DB.
- **Bằng chứng:**
  ```
  $ Remove-Item Env:\DB_HOST,Env:\DB_USER,Env:\DB_USERNAME,Env:\DB_PASSWORD -ErrorAction SilentlyContinue
  $ docker compose -f docker-compose.production.yaml config
  level=warning msg="The \"DB_HOST\" variable is not set. Defaulting to a blank string."
  level=warning msg="The \"DB_PASSWORD\" variable is not set. Defaulting to a blank string."
        DB_HOST: ""
        DB_PASSWORD: ""
  ```
- **Fix:** dùng cú pháp bắt buộc của Compose: `DB_HOST: ${DB_HOST:?}` và `DB_PASSWORD: ${DB_PASSWORD:?}`.
  Coolify hỗ trợ và chặn deploy khi biến bắt buộc rỗng ("`${DATABASE_URL:?}` marks the value as required.
  Coolify prevents deployment while a required Service variable is empty").
- **Trạng thái:** OPEN (không chặn merge) — cần task theo dõi, xem mục "Điều kiện merge" bên dưới

### 2. MINOR — `config/db.php` không được persist ⇒ sau mỗi lần deploy site quay về installer công khai

- **Vị trí:** `docker-compose.production.yaml:56-59` (chỉ mount `vvveb_storage`, `vvveb_page_cache`), `index.php:70-78`,
  `system/core/startup.php:363-374`
- **Vấn đề:** runtime app **không** đọc `DB_*` env — nó đọc `config/db.php`
  (`startup.php:363-374`: `config('db.connections.default')`). `config/` không nằm trong volume ⇒ khi Coolify
  recreate container, `config/db.php` mất ⇒ `index.php:70-78` chuyển `APP=install` và redirect mọi request
  (kể cả `/`) sang `/install/index.php` ⇒ **installer được mở cho công chúng**, ai cũng có thể tự cài lại với
  tài khoản admin do họ tạo. `DB_*` env chỉ được dùng để prefill installer
  (`install/controller/index.php:236-241` đọc `$_ENV['DB_HOST']`…) và để chờ DB trong entrypoint; nếu đổi
  `DB_PASSWORD` ở Coolify thì app **không** tự nhận password mới.
- **Bằng chứng:**
  ```
  $ grep -n "DB_HOST\|DB_PORT" system/core/startup.php      # chỉ đọc từ config/db.php
  369: define('DB_HOST',   $connection['host'] ?? '');
  374: define('DB_PORT',   $connection['port'] ?? null);
  $ grep -rn "getenv(\|_ENV\[" --include=*.php .
  install/controller/index.php:239: if (isset($_ENV[$env])) {     # chỉ installer đọc DB_* env
  config/app.php:33-34: 'cronkey' => getenv('CRON_KEY'), 'key' => getenv('APP_KEY')
  ```
  (`config/` không có trong `volumes:` của compose production ⇒ `docker compose config` không sinh volume cho `config`.)
- **Fix:** persist `config/` (volume riêng + seed lần đầu trong entrypoint, ví dụ copy `config/*.php` mặc định
  vào volume) **hoặc** ghi rõ trong PR body/runbook: sau mỗi deploy phải chạy lại installer (`fresh-install.sh`)
  và phải chặn `/install/` khi site đã cài. Ngoài ra nên ghi rõ quy trình đổi mật khẩu DB.
- **Trạng thái:** OPEN (không chặn merge) — đây là hành vi **pre-existing** (compose cũ cũng không mount `config/`),
  PR này không gây regression, nhưng đây đúng là thời điểm bàn giao production nên cần ghi vào runbook

### 3. MINOR — Dữ liệu MySQL cũ không được migrate sang DB ngoài, biện minh trong plan chưa đúng trọng tâm

- **Vị trí:** `vc-dev/issues/docker-production-external-db.md:49-50`
- **Vấn đề:** plan ghi "Rủi ro: mất dữ liệu MySQL cũ … Giảm thiểu: volume `vvveb_db_data` không bị xoá". Volume còn
  nằm trên host nhưng app **không dùng nó nữa**; DB ngoài do Coolify tạo là database rỗng. Nếu production đã có
  dữ liệu thật thì cần dump từ volume cũ rồi restore vào DB ngoài — hiện PR body và plan chưa nêu bước này.
- **Bằng chứng:**
  ```
  $ git diff --name-status origin/vc-dev...HEAD
  A	docker-compose.production.full.yaml
  M	docker-compose.production.yaml
  M	docker-entrypoint.sh
  A	fresh-install.sh
  M	vc-dev/instructions/skills.md
  M	vc-dev/issues/README.md
  A	vc-dev/issues/docker-production-external-db.md
  $ docker compose -f docker-compose.production.yaml config | Select-String "^  \w+:"
    php:                      # không còn service db
  ```
- **Fix:** bổ sung vào PR body + `## Handoff Summary` của plan: (a) production chưa có dữ liệu thật ⇒ cài mới bằng
  installer trên DB ngoài; (b) đã có dữ liệu ⇒ `mysqldump` từ volume `vvveb_db_data` rồi `mysql` restore vào DB ngoài
  trước khi deploy.
- **Trạng thái:** OPEN (không chặn merge)

### 4. MINOR — Lý do "production bắt buộc dùng MySQL" trong plan không chính xác về mặt kỹ thuật

- **Vị trí:** `vc-dev/issues/docker-production-external-db.md:37-39` và `vc-dev/issues/docker-production-external-db.md:91-94`
- **Vấn đề:** `REQUIRED_EXTENSIONS` là danh sách **PHP extension** bắt buộc của installer, không phải engine DB;
  installer hỗ trợ `engine=sqlite`; và khi thiếu extension, `checkRequirements()` chỉ **hiển thị** cảnh báo,
  **không chặn** cài đặt. Kết luận "production dùng MySQL" vẫn đúng, nhưng lý do nêu trong plan sai.
- **Bằng chứng:**
  ```
  install/controller/index.php:41   define('REQUIRED_EXTENSIONS', ['mysqli', 'mysqlnd', 'xml', ...]);
  install/controller/index.php:48   private $config = ['engine' => 'mysqli', ..., 'port' => null, ...];
  install/controller/index.php:96-118  checkRequirements() chỉ trả về $notMet (hiển thị), không throw
  install/controller/index.php:148-150 if ($config['engine'] == 'sqlite') { $config['host'] = DIR_STORAGE.'sqlite/vvveb.db'; }
  system/db/sqlite.php:85           self :: $link = new \SQLite3($filename);   # cần ext sqlite3
  Dockerfile.production              docker-php-ext-install ... mysqli ...      # KHÔNG cài sqlite3
  ```
- **Fix:** sửa wording trong `Scope & Impact Analysis` và `Key Decisions #1`: lý do đúng là **image production không
  có ext `sqlite3`** (driver `system/db/sqlite.php` cần class `\SQLite3`); `mysqli`/`mysqlnd` chỉ là extension bắt
  buộc của installer chứ không quyết định engine.
- **Trạng thái:** OPEN (không chặn merge) — chỉ sửa tài liệu, không ảnh hưởng code

### 5. MINOR — `fresh-install.sh` xóa `config/sites.php` (file đang được git track) và không cảnh báo hệ quả

- **Vị trí:** `fresh-install.sh:38-46` (xóa `config/db.php`), `fresh-install.sh:49-56` (xóa `config/sites.php`)
- **Vấn đề:** `config/sites.php` **đang được git track** ⇒ chạy script trong checkout dev sẽ để lại
  `git status` báo file bị xóa (không tự phục hồi). Chạy trên production: xóa `config/db.php` ⇒ theo
  `index.php:70-78` site **mở installer cho công chúng** cho tới khi cài lại xong — script chỉ in
  "Hãy truy cập trang web để bắt đầu cài đặt lại (installer sẽ chạy tự động)" (cụm "installer sẽ chạy tự động"
  cũng không đúng: installer không tự chạy, người vận hành phải tự mở trang và điền form).
- **Bằng chứng:**
  ```
  $ git ls-files config/sites.php
  config/sites.php
  $ git ls-files storage/sqlite
  storage/sqlite/.htaccess
  storage/sqlite/vvveb.db
  ```
  Sandbox (chạy script trong thư mục tạm, không chạm repo):
  ```
  ✓ Đã xóa config/db.php
  ✓ Đã xóa config/sites.php
  ✓ Đã xóa folder storage/cache | compiled-templates | logs | backup | sqlite | model
  ✓ Đã xóa nội dung public/page-cache | public/assets-cache | public/image-cache
  Còn lại: config/app.php, public/media/keep.png, keep-root.txt   # không xóa gì ngoài phạm vi
  ```
- **Fix:** thêm cảnh báo trước khi xóa (`config/db.php` sẽ mở lại installer công khai — hoàn tất cài đặt ngay,
  chặn truy cập `/install/` nếu site đã cài) và gợi ý `git checkout config/sites.php` khi chạy ở dev.
- **Trạng thái:** OPEN (không chặn merge)

### 6. NIT — `docker-entrypoint.sh` đổi indent khối `php cli.php install` gây nhiễu diff

- **Vị trí:** `docker-entrypoint.sh:43-53` (thụt từ 2 space sang 4 space so với bản cũ)
- **Vấn đề:** không đổi ngữ nghĩa (shell không quan tâm indent) nhưng làm diff khó đọc, dễ tưởng là đổi luồng.
- **Fix:** giữ indent 2 space như bản cũ.
- **Trạng thái:** OPEN

### 7. NIT — `fresh-install.sh` đặt ở root repo trong khi `agent-guide.md` §3 yêu cầu script tiện ích ở `vc-dev/scripts/**`

- **Vị trí:** `fresh-install.sh` (root), `vc-dev/instructions/agent-guide.md:58`
- **Bằng chứng:** `git ls-files "*.sh"` → `build.sh`, `docker-entrypoint.sh`, `fresh-install.sh`;
  `.dockerignore` loại `vc-dev/` khỏi build context ⇒ script đặt trong `vc-dev/scripts/` sẽ không có trong image.
- **Fix:** nếu giữ ở root, ghi chú lý do vào plan để lần sau không bị coi là vi phạm quy ước.
- **Trạng thái:** REJECTED — chấp nhận lý do: script chạy trên server/container ở app root (`SCRIPT_DIR` = app root),
  `vc-dev/` không nằm trong image production; có tiền lệ `docker-entrypoint.sh` và `build.sh` ở root.

### 8. NIT — `.dockerignore` chưa loại `docker-compose.production*.yaml` (file mới được copy vào image)

- **Vị trí:** `.dockerignore` (có `docker-compose.yaml`, `docker-compose-apache.yaml`, `coolify-compose.yaml`, không có `docker-compose.production*.yaml`)
- **Vấn đề:** `docker-compose.production.full.yaml` mới sẽ được `COPY` vào image; không ảnh hưởng chạy app nhưng thừa.
- **Fix:** thêm `docker-compose.production*.yaml` vào `.dockerignore`.
- **Trạng thái:** OPEN

## Claim đã xác minh đúng

- Claim "Bỏ service `db`, volume `vvveb_db_data`, `depends_on` khỏi `docker-compose.production.yaml`" — ĐÚNG:
  `docker compose -f docker-compose.production.yaml config` chỉ còn `services.php`; `git diff origin/vc-dev...HEAD`
  cho thấy khối `db`, `vvveb_db_data` và `depends_on` bị gỡ.
- Claim "`docker-compose.production.full.yaml` là bản cấu hình cũ làm phương án rollback" — ĐÚNG, **giống hệt từng byte**:
  `git rev-parse origin/vc-dev:docker-compose.production.yaml` = `git rev-parse HEAD:docker-compose.production.full.yaml`
  = `411c06845a4785762d5a7055aa2c99862fdb2b22`; `git diff origin/vc-dev:docker-compose.production.yaml HEAD:docker-compose.production.full.yaml`
  không có output; `docker compose -f docker-compose.production.full.yaml config` vẫn render đủ service `db` + `php`.
- Claim "`docker compose -f docker-compose.production.yaml config` render thành công, `DB_HOST`/`DB_PORT`/`DB_USER`/`DB_PASSWORD` lấy đúng" — ĐÚNG:
  set `DB_HOST=coolify-db, DB_PORT=3307, DB_DATABASE=vvveb_prod, DB_USER=coolify_user, DB_PASSWORD=s3cret` ⇒
  render ra `DB_HOST: coolify-db`, `DB_PORT: "3307"`, `DB_DATABASE: vvveb_prod`, `DB_USER: coolify_user`, `DB_PASSWORD: s3cret`.
- Claim "`DB_USER` fallback `${DB_USER:-${DB_USERNAME:-vvveb}}` đúng khi chỉ set `DB_USERNAME`" — ĐÚNG:
  chỉ set `DB_USERNAME=coolify_user` ⇒ `DB_USER: coolify_user`; không set gì ⇒ `DB_USER: vvveb` (fallback cuối).
- Claim "`bash -n docker-entrypoint.sh` và `bash -n fresh-install.sh` không lỗi cú pháp" — ĐÚNG:
  `& "C:\Program Files\Git\bin\bash.exe" -n docker-entrypoint.sh` → exit 0; tương tự `fresh-install.sh` → exit 0.
- Claim "connect check trả exit code khác 0 khi DB không phản hồi" — ĐÚNG:
  `php -r "mysqli_report(MYSQLI_REPORT_OFF); $c = @new mysqli('127.0.0.1','root','',null,3306); exit($c && !$c->connect_errno ? 0 : 1);"`
  → `connect_errno=2002`, `EXITCODE=1` (trước đây `new mysqli(...)` trả 0 vì chỉ warning ⇒ vòng lặp cũ coi như đã kết nối).
- Claim "CLI install nhận tham số `port`" — ĐÚNG: `install/controller/index.php:48` có key `'port'` trong `$config`;
  `cli.php:82-83` nạp tham số vào `$_GET`/`$_POST`; `system/core/request.php:51-52` map `$get = &$_GET`, `$post = &$_POST`;
  `install/controller/index.php:243-245` đọc `get['port']`, `:140-144` đọc `post['port']` ⇒ `port=3306` được ghi vào `config/db.php`.
- Claim "`VVVEB_HOST` không được đọc ở đâu trong code" — ĐÚNG: grep toàn repo chỉ còn `docker-compose.yaml:26`,
  `docker-compose.production.full.yaml:48` và `vc-dev/instructions/skills.md:316`; không có PHP nào đọc `VVVEB_HOST`/`VVVEB_PASSWORD`.
- Claim "Không có secret / file DB / đường dẫn cấm trong diff" — ĐÚNG: `git diff --name-status origin/vc-dev...HEAD` chỉ có 7 file
  (compose + script + `vc-dev/` docs); không có `storage/sqlite/vvveb.db`, không có `vc-dev/wip/*`, không có `config/db.php`;
  grep diff không thấy secret hardcode (chỉ `${...}` placeholder).
- Claim "`fresh-install.sh` chỉ xóa trong các thư mục runtime dự kiến" — ĐÚNG (sandbox test ở trên): giữ lại
  `config/app.php`, `public/media/*` và mọi file khác; không có `rm` nào trỏ ra ngoài app root.
- Claim "`.dockerignore` không loại `docker-entrypoint.sh`/`php.ini` (file build cần), có loại `storage/` và `vc-dev/`" — ĐÚNG:
  `.dockerignore` chứa `storage/`, `vc-dev/`, `config/db.php`; **không** chứa `docker-entrypoint.sh`/`php.ini`
  (có comment "needed for COPY in Dockerfile").
- Claim "app đọc `DB_*` env lúc runtime" — KHÔNG ĐÚNG theo nghĩa "app dùng env để nối DB": runtime chỉ đọc `config/db.php`
  (`system/core/startup.php:363-374`). `DB_*` env chỉ được **installer** đọc để prefill form
  (`install/controller/index.php:236-241` qua `$_ENV`, `install/template/common.tpl:5-21` bind vào input) và dùng trong
  vòng chờ DB của entrypoint. Hàm vi này đúng và quan trọng cho deploy (xem Finding #2, #3).

## Claim không xác minh được

- Claim "Coolify inject `DB_USERNAME` cho Predefined Database MySQL" — CHƯA XÁC MINH ĐƯỢC trong repo: cần deploy Coolify thật.
  Hỗ trợ từ tài liệu/mẫu Coolify (biến chuẩn của Coolify cho DB là `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`,
  `DB_PASSWORD`), nhưng không kiểm chứng được bằng lệnh trong repo này. Vì vậy fallback `DB_USER` vẫn cần giữ.
- Claim "App và container DB phải cùng Docker network để resolve được hostname DB" — CHƯA XÁC MINH ĐƯỢC (thuộc ops):
  cần Coolify thực tế để xác nhận "Connect to Predefined Database" gắn DB vào network của app.
- Claim "Coolify deploy thật: app kết nối được MySQL ngoài, `/install/` chạy được, healthcheck healthy" — CHƯA XÁC MINH ĐƯỢC:
  máy reviewer không có Docker daemon (`docker compose config` chạy được vì không cần daemon), nên không dựng được stack
  end-to-end. Test này vẫn đang PENDING trong plan (đúng như plan tự ghi).
- Claim "Volume `vvveb_db_data` không bị xoá khi deploy compose mới" — CHƯA XÁC MINH ĐƯỢC (cần Coolify/host thật):
  suy ra từ hành vi chuẩn của Docker (named volume không bị xoá khi `compose down` không kèm `-v`), nhưng reviewer
  không quan sát được trên host production.

## Quyết định

**APPROVED WITH FOLLOW-UPS.** Không có `BLOCKER`/`MAJOR` OPEN. Diff không làm hỏng deploy production:
khi `DB_HOST`/`DB_PASSWORD`/`DB_PORT`/`DB_DATABASE`/`DB_USER` được set đúng trong Coolify Variables (hoặc dùng
"Connect to Predefined Database") và app cùng network với DB, thì container boot, chờ DB bằng `connect_errno`,
installer prefill sẵn thông tin kết nối từ `$_ENV`, và app nối DB qua `config/db.php` như bình thường.

Các điểm còn lại là rủi ro **vận hành** (không phải hồi quy code): biến DB thiếu mà deploy vẫn "thành công" (F1),
`config/db.php` không persist nên installer có thể bị mở công khai sau mỗi deploy (F2), dữ liệu cũ không tự migrate (F3),
và hai chỗ tài liệu/script cần chỉnh (F4, F5).

**Điều kiện merge (theo `development.md` §6.3):** các finding `MINOR`/`NIT` phải có file/task theo dõi. Tác giả thêm
các task sau vào plan hiện tại `vc-dev/issues/docker-production-external-db.md` (hoặc tạo `vc-dev/issues/<slug>.md`
riêng) rồi link file đó trong PR body — reviewer sẽ chạy lại lệnh xác minh và đóng finding:

- T9 — dùng `${DB_HOST:?}` / `${DB_PASSWORD:?}` trong `docker-compose.production.yaml` (F1)
- T10 — quyết định + tài liệu cách persist `config/db.php` sau mỗi deploy, chặn `/install/` khi site đã cài (F2)
- T11 — bổ sung bước dump/restore dữ liệu từ `vvveb_db_data` sang DB ngoài vào PR body/runbook (F3)
- T12 — sửa wording lý do "bắt buộc dùng MySQL" trong plan (F4) và thêm cảnh báo cho `fresh-install.sh` (F5)
- T13 — dọn indent `docker-entrypoint.sh` (F6) + thêm `docker-compose.production*.yaml` vào `.dockerignore` (F8)

Có thể merge khi `gh pr view 38 --json headRefOid --jq .headRefOid` vẫn bằng `44fd69b9116565d1700b58c4d788f7b06e185e20`
(hoặc đã có re-review cho commit mới hơn).

## Checklist hoàn tất review (trước khi merge)

- [x] File `vc-dev/pr-review/pr-38-docker-production-external-db.md` tồn tại.
- [x] `Commit review` khớp `gh pr view 38 --json headRefOid` = `44fd69b9116565d1700b58c4d788f7b06e185e20`.
- [x] Verdict cuối `APPROVED WITH FOLLOW-UPS`; không còn `BLOCKER`/`MAJOR` OPEN.
- [ ] Mọi follow-up `MINOR`/`NIT` có link file theo dõi — tác giả thêm T9–T13 vào `vc-dev/issues/docker-production-external-db.md` và link trong PR body.
- [x] Index `vc-dev/pr-review/README.md` đã cập nhật.
- [ ] Re-review delta nếu có commit mới sau `44fd69b` (head SHA lúc merge phải khớp commit đã review).
