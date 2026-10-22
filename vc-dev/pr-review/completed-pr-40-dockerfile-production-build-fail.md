# PR #40 — `fix/dockerfile-production-build-permissions`

- **Reviewer:** independent session (Kilo — `kilo-auto/free`), context sạch, không phải tác giả PR
- **Ngày:** 2026-10-22
- **Base:** `vc-dev` · **Head:** `fix/dockerfile-production-build-permissions` · **Commit review (code runtime):** `510d2f32f156f5ae6129eb7207d3fa118caf0b01`
- **PR:** #40 — "fix(docker): sua build production fail - chmod vendor, USER www-data, CRLF shebang"
- **Commit trong PR:** `510d2f3` (1 commit, 4 file: `.gitattributes`, `Dockerfile.production`, `vc-dev/issues/README.md`, `vc-dev/issues/dockerfile-production-build-fail.md`) + `2e80b16` (docs-only, 3 file, toàn bộ dưới `vc-dev/`)
- **Code (runtime/config) được review tại:** `510d2f3` — commit `2e80b16` là **docs-only** (`vc-dev/issues/**`, `vc-dev/pr-review/**`), không đụng file runtime nào ⇒ nội dung deploy y hệt bản đã build `--no-cache` và chạy container ở review đầu
- **Verdict ban đầu:** APPROVED WITH FOLLOW-UPS
- **Verdict cuối:** APPROVED WITH FOLLOW-UPS (re-review 1 tại `2e80b16` — verdict **không đổi**)

## Tóm tắt

PR sửa build production Coolify đang fail (`exit code: 1` ở step tạo quyền) bằng 3 thay đổi trong `Dockerfile.production`
(đưa `chmod vendor` sau `composer install`, chuyển `USER www-data` xuống cuối file, thêm `sed -i 's/\r$//'` cho entrypoint)
và 1 thay đổi `.gitattributes` (`*.sh text eol=lf`).

Reviewer đã **tự build lại từ đầu** (không dùng lại cache của tác giả) và **chạy container** để kiểm chứng:

- `docker build --no-cache -f Dockerfile.production -t vvveb-review-nocache:latest .` → **thành công** (build context 367.06 kB, không có `vendor/` stale trong context).
- Container chạy được: `HTTP 200` (trang installer), Apache master PID 1 chạy dưới `www-data` và bind được port 80,
  app ghi được `storage/`, `config/`, `public/media/`, `public/page-cache/`, `vendor/` thuộc `www-data`.
- Cả 3 lỗi riêng biệt trong `Dockerfile.production` cũ (`origin/vc-dev`) được tái hiện độc lập bằng lệnh thật
  (2 lỗi gây fail Coolify: `chmod vendor` sớm và `USER www-data` sớm; 1 lỗi CRLF chỉ xảy ra khi build local trên Windows).

Không có `BLOCKER`/`MAJOR`. Toàn bộ finding là `MINOR`/`NIT` về **độ chính xác của mô tả** và **nợ kỹ thuật ngoài phạm vi**;
cần thêm task theo dõi trong plan của PR (xem Finding #4) theo `development.md` §6.3.

**Re-review 1 (2026-10-22, head `2e80b16`):** tác giả push thêm 1 commit **docs-only** sau SHA đã review (`510d2f3`).
Delta chỉ gồm 3 file dưới `vc-dev/` (plan + review record + index); `Dockerfile.production` và `.gitattributes`
**không đổi** (diff rỗng) ⇒ **không cần build lại image**, nội dung deploy y hệt bản đã build `--no-cache` ở review đầu.
Commit này đã sửa wording Root cause #3 và nhãn mức can thiệp (F1, F2), thêm task theo dõi T10/T11/T12 và đánh dấu
T7 `COMPLETED` (F4); F3 (`Dockerfile` dev) vẫn OPEN nhưng đã có T12. Chi tiết ở mục "Re-review 1".

## Bằng chứng build (do reviewer chạy, không tin PR body)

```
$ git rev-parse HEAD
510d2f32f156f5ae6129eb7207d3fa118caf0b01        # = gh pr view 40 --json headRefOid --jq .headRefOid

$ docker build -f Dockerfile.production -t vvveb-review-check:latest .
#1..#19 DONE 0.2s  -> thành công (mọi layer CACHED từ build của tác giả)

$ docker build --no-cache -f Dockerfile.production -t vvveb-review-nocache:latest .
#7  transferring context: 367.06kB 0.3s done          <- context KHÔNG chứa vendor/ stale
#15 [stage-0  9/12] RUN mkdir -p storage/cache ... && chown ... && chmod -R 755 public app config system rest graphql plugins
#15 DONE 6.6s                                          <- step từng fail trên Coolify: giờ PASS
#16 [stage-0 10/12] RUN composer install --no-dev --optimize-autoloader --no-interaction && chown -R www-data:www-data vendor && chmod -R 755 vendor
#16 0.462 Installing dependencies from lock file
#16 0.909 Nothing to install, update or remove
#16 0.915 Generating optimized autoload files
#16 DONE 0.9s
#17 [stage-0 11/12] COPY docker-entrypoint.sh /usr/local/bin/        DONE
#18 [stage-0 12/12] RUN sed -i 's/\r$//' ... && chmod +x ...       DONE 0.2s
#19 naming to docker.io/library/vvveb-review-nocache:latest done   -> BUILD SUCCESS (exit 0)

$ docker rm -f vvveb-verify; docker run -d --name vvveb-verify -p 18083:80 vvveb-prod-test:latest
config/db.php không tồn tại - container sẽ redirect về /install/ khi truy cập web
[AH00163: Apache/2.4.69 (Debian) PHP/8.3.36 configured -- resuming normal operations]
[AH00094: Command line: 'apache2 -D FOREGROUND']

$ Invoke-WebRequest http://localhost:18083/
HTTP 200

$ docker exec vvveb-verify bash -c "id; touch storage/cache/_t && echo W_STORAGE_OK; touch config/_t && echo W_CONFIG_OK; ls -ld /var/www/html/vendor /var/www/html/storage /var/www/html/config"
uid=33(www-data) gid=33(www-data) groups=33(www-data)
W_STORAGE_OK
W_CONFIG_OK
drwxr-xr-x 1 www-data www-data /var/www/html/config
drwxr-xr-x 1 www-data www-data /var/www/html/storage
drwxr-xr-x 3 www-data www-data /var/www/html/vendor
```

Bổ sung (runtime, image build từ đầu `vvveb-review-nocache:latest`):

```
$ docker exec vvveb-review-nc bash -c "ps -o pid,user,comm -e | head -3; stat -c '%a %U:%G %n' /usr/local/bin/docker-entrypoint.sh; head -c 20 /usr/local/bin/docker-entrypoint.sh | od -c | head -2"
PID   USER     COMMAND
1     www-data apache2      <- Apache master chạy dưới www-data và vẫn bind port 80
755 root:root /usr/local/bin/docker-entrypoint.sh
0000000   #   !   /   b   i   n   /   b   a   s   h  \n   s   e   t   <- shebang LF, không có CR

$ docker exec vvveb-review-nc bash -c "curl -f http://localhost:80/ >/dev/null && echo HEALTHCHECK_OK"
HEALTHCHECK_OK                 <- healthcheck trong docker-compose.production.yaml dùng đúng lệnh này (/usr/bin/curl có sẵn)
```

## Re-review 1 — 2026-10-22 (head `2e80b16`)

Tác giả push thêm 1 commit sau SHA đã review (`510d2f3`). Reviewer đã re-review delta mới và xác minh từng claim
bằng lệnh thật (không tin mô tả của PR body). **Không build lại image** vì code runtime không đổi (diff rỗng).

- **Head SHA re-review:** `2e80b16ee0feebba78bfe7799923e9883f073f0b`
- **Delta commit(s) giữa 2 lần review:** `2e80b16` — "docs(vc-dev): review độc lập PR #40 + sửa mô tả root cause + follow-up T10-T12"
- **Commit review (cập nhật):** code (runtime/config) vẫn được review tại `510d2f32f156f5ae6129eb7207d3fa118caf0b01`;
  commit `2e80b16` là **docs-only** — mang chính file review record này + cập nhật index + sửa wording trong plan.
- **Kết luận:** delta **docs-only** (3 file, toàn bộ dưới `vc-dev/`), **không có file runtime nào đổi** ⇒ nội dung deploy
  giống hệt bản đã duyệt ⇒ verdict giữ nguyên `APPROVED WITH FOLLOW-UPS`; 3/4 finding đã đóng, F3 còn OPEN nhưng
  **đã có task theo dõi T12** ⇒ không còn điều kiện bắt buộc nào chặn merge.

### Bằng chứng (các lệnh đã chạy)

```
$ gh pr view 40 --json headRefOid --jq .headRefOid
2e80b16ee0feebba78bfe7799923e9883f073f0b

$ git log --oneline 510d2f3..2e80b16
2e80b16 docs(vc-dev): review doc lap PR #40 + sua mo ta root cause + follow-up T10-T12

$ git diff --name-status 510d2f3..2e80b16
M       vc-dev/issues/dockerfile-production-build-fail.md
M       vc-dev/pr-review/README.md
A       vc-dev/pr-review/pr-40-dockerfile-production-build-fail.md

$ git diff 510d2f3..2e80b16 -- Dockerfile.production .gitattributes
(rỗng — không có dòng nào)

$ git diff --numstat 510d2f3..2e80b16 -- Dockerfile.production .gitattributes
(rỗng)

$ git ls-tree 510d2f3 -- vc-dev/pr-review/ | Select-String "pr-40"
(không có kết quả — file review record chưa được track ở SHA đã review)

$ git log --oneline -- vc-dev/pr-review/pr-40-dockerfile-production-build-fail.md
2e80b16 docs(vc-dev): review doc lap PR #40 + sua mo ta root cause + follow-up T10-T12

$ git show 2e80b16:vc-dev/issues/dockerfile-production-build-fail.md | Select-String -Pattern "T7 —|T10 \||T11 \||T12 \||Mức can thiệp core|KHÔNG phải nguyên nhân fail Coolify"
- [x] COMPLETED: T7 — Review độc lập PR #40 bằng subagent `general` (context sạch): verdict
| T10 | F1 | Mô tả root cause trong PR body/plan đã sửa ở PR này (CRLF không phải nguyên nhân fail Coolify). …
| T11 | F2 | Nhãn mức can thiệp core trong plan/PR đã sửa ở PR này ("ngoài thang đo core"). …
| T12 | F3 | `Dockerfile` dev (`php:8.3-fpm`) vẫn đặt `USER www-data` trước step cần root. …
- **Mức can thiệp core (architecture.md §4.2):** ngoài thang đo tính năng (mức 1–6) — chỉ build/deploy
3. **CRLF làm hỏng shebang khi build trên Windows — KHÔNG phải nguyên nhân fail Coolify.** Coolify clone

$ git show 2e80b16:vc-dev/pr-review/pr-40-dockerfile-production-build-fail.md | Select-String "vc-dev/issues/dockerfile-production-build-fail.md" | Measure-Object
plan-file-references: 10        <- review record có link file plan (điều kiện §6.3)

$ git diff 510d2f3..2e80b16 | Select-String -Pattern "(?i)(password|secret|api[_-]?key|token|BEGIN .*PRIVATE KEY)"
(chỉ trúng từ "secret" trong văn xuôi của review record; không có `storage/**`, `vc-dev/wip/**`, `config/db.php`)
```

### Kết quả theo từng finding sau re-review 1

| # | Finding | Trạng thái | Bằng chứng / theo dõi |
|---|---|---|---|
| 1 | MINOR — mô tả "3 root cause" không chính xác cho #3 (CRLF) | **FIXED** | Commit `2e80b16`: plan `vc-dev/issues/dockerfile-production-build-fail.md:38-43` ghi "CRLF làm hỏng shebang khi build trên Windows — **KHÔNG phải nguyên nhân fail Coolify**"; PR body §Root cause ghi "**2 nguyên nhân gây fail deploy Coolify**" và "**Không phải nguyên nhân deploy fail** … Coolify clone trên Linux nên file luôn LF". Theo dõi: **T10** (`...:88`). |
| 2 | NIT — nhãn "mức 1" không khớp `architecture.md` §4.2 | **FIXED** | Commit `2e80b16`: plan `:52-53` ghi "**ngoài thang đo tính năng (mức 1–6)** — chỉ build/deploy config (`Dockerfile.production`, `.gitattributes`), không sửa file PHP core"; PR body §"Mức can thiệp core" dùng đúng wording "Ngoài thang đo tính năng (architecture.md §4.2) — chỉ build/deploy config, không sửa code PHP core". Theo dõi: **T11** (`...:89`). |
| 3 | MINOR — `Dockerfile` (dev) giữ pattern `USER www-data` trước step cần root | **STILL OPEN** | Không sửa trong delta (đúng phạm vi: image dev không fail — xem bằng chứng ở review đầu). Đã có task theo dõi **T12** (`...:90`) — nên dời `USER www-data` xuống cuối `Dockerfile` dev ở PR sau. **Không chặn merge** (ngoài phạm vi PR này). |
| 4 | MINOR — finding chưa có task theo dõi trong plan (§6.3) | **FIXED** | Commit `2e80b16`: bảng `### Follow-up (từ review PR #40)` có T10/T11/T12 (`...:84-90`) và T7 đã chuyển `COMPLETED` (`...:78-80`). Review record này link file plan (10 tham chiếu) ⇒ đủ điều kiện §6.3. |

### Claim của tác giả ở re-review 1 — đã xác minh

- Claim "delta chỉ đụng file dưới `vc-dev/`, không có code/runtime" — **ĐÚNG**:
  `git diff --name-status 510d2f3..2e80b16` chỉ có 3 file `vc-dev/**`; `git diff 510d2f3..2e80b16 -- Dockerfile.production .gitattributes`
  và `--numstat` đều **rỗng** ⇒ code deploy giống hệt bản đã build `--no-cache` + chạy container ở review đầu.
- Claim "PR body ghi 2 root cause (chmod vendor trước composer install; USER www-data trước step cần root)" — **ĐÚNG**
  (đọc `gh pr view 40 --json body`): mục Root cause liệt kê đúng 2 nguyên nhân với cơ chế và thông điệp lỗi tương ứng.
- Claim "PR body đánh dấu CRLF **không phải** nguyên nhân deploy fail" — **ĐÚNG**: "**Không phải nguyên nhân deploy fail**
  (nhưng vẫn fix trong PR này): CRLF làm hỏng shebang — chỉ xảy ra khi build trên Windows (`core.autocrlf=true`);
  Coolify clone trên Linux nên file luôn LF."
- Claim "PR body dùng wording mức can thiệp đã sửa" — **ĐÚNG**: "Ngoài thang đo tính năng (architecture.md §4.2) —
  chỉ build/deploy config, không sửa code PHP core." (không còn nhãn "mức 1").
- Claim "F1/F2/F3 có tracking T10/T11/T12 và T7 đã COMPLETED" — **ĐÚNG**: blob plan tại `2e80b16` có đủ 3 dòng
  follow-up (T10→F1, T11→F2, T12→F3) và T7 ở trạng thái `[x] COMPLETED`.
- Claim "review record được commit kèm" — **ĐÚNG**: file xuất hiện với trạng thái `A` trong
  `git diff --name-status 510d2f3..2e80b16`; trước đó chưa tồn tại (`git ls-tree 510d2f3 -- vc-dev/pr-review/`
  không có entry `pr-40-*`) ⇒ đúng tiền lệ PR #13 (review record được commit docs-only) và PR #39.
- Claim "không có secret / file DB / đường dẫn cấm trong delta" — **ĐÚNG**: grep `password|secret|api key|token|PRIVATE KEY`
  trong diff chỉ trúng từ "secret" trong văn xuôi; không có `storage/**`, `vc-dev/wip/**`, `config/db.php`.

## Findings

### 1. MINOR — Mô tả "3 root cause của lỗi Coolify" không chính xác cho #3 (CRLF)

- **Vị trí:** PR body §"Root cause" mục 3 (`CRLF làm hỏng shebang — checkout Windows (core.autocrlf=true)`);
  `vc-dev/issues/dockerfile-production-build-fail.md:38-40` (`3. **CRLF làm hỏng shebang.** ... Verify: file trong working tree có 66 dòng CRLF`)
- **Vấn đề:** blob `docker-entrypoint.sh` **trong git là LF** ở cả base lẫn head (0 byte CR) ⇒ Coolify clone trên Linux
  ⇒ file trong build context là LF ⇒ **shebang KHÔNG hỏng trong build Coolify**. CRLF chỉ sinh ở *working tree* Windows
  do `core.autocrlf=true` trong global gitconfig ⇒ lỗi `exec /usr/local/bin/docker-entrypoint.sh: no such file or directory`
  chỉ xảy ra khi **build image local trên Windows** (đúng là bước tác giả gặp khi test local), không phải nguyên nhân
  build fail trên Coolify. Cách fix đưa ra (`.gitattributes` + `sed`) vẫn **đúng và hữu ích** (defense in depth 2 lớp,
  build không còn phụ thuộc line ending), nhưng mô tả "3 lỗi đều từ commit 7ed91bb / đều là nguyên nhân lỗi Coolify" là
  không chính xác cho #3 ⇒ có thể gây hiểu nhầm khi tra cứu sự cố sau này.
- **Bằng chứng:**
  ```
  $ cmd /c "git cat-file blob origin/vc-dev:docker-entrypoint.sh" > tmp.sh
  blob@origin/vc-dev: bytes=3153 CR=0
  blob@HEAD:         bytes=3153 CR=0
  $ git diff origin/vc-dev HEAD -- docker-entrypoint.sh      # rỗng — blob không đổi
  $ git ls-files --eol docker-entrypoint.sh
  i/lf    w/lf    attr/text eol=lf       docker-entrypoint.sh
  $ git config --list --show-origin | findstr autocrlf
  file:C:/Program Files/Git/etc/gitconfig  core.autocrlf=true
  file:C:/Users/GIANGNH2/.gitconfig       core.autocrlf=true
  ```
- **Fix:** sửa wording ở PR body và plan mục Root cause #3 thành: "lỗi khi build **local trên Windows** (checkout CRLF),
  không phải nguyên nhân fail trên Coolify; `.gitattributes` + `sed` là 2 lớp bảo vệ để build không phụ thuộc line ending".
- **Trạng thái (re-review 1 @ `2e80b16`):** **FIXED** — commit `2e80b16` đã sửa wording ở **cả plan lẫn PR body**:
  plan `vc-dev/issues/dockerfile-production-build-fail.md:38-43` ghi "CRLF làm hỏng shebang khi build trên Windows —
  **KHÔNG phải nguyên nhân fail Coolify**" (kèm lý do blob trong git luôn là LF vì Coolify clone trên Linux);
  PR body §Root cause ghi "**2 nguyên nhân gây fail deploy Coolify**" và mục "**Không phải nguyên nhân deploy fail**".
  Theo dõi: **T10** — `vc-dev/issues/dockerfile-production-build-fail.md:88`. Không còn chặn merge.

### 2. NIT — Nhãn "mức 1" không khớp thang đo `architecture.md` §4.2

- **Vị trí:** PR body §"Mức can thiệp core" (`mức 1 (build/deploy config)`);
  `vc-dev/issues/dockerfile-production-build-fail.md:50` (`- **Mức can thiệp core (architecture.md §4.2):** mức 1 (build/deploy config)`)
- **Vấn đề:** §4.2 định nghĩa mức 1 = "Thêm **plugin** mới trong `plugins/`". PR này không thêm plugin, chỉ sửa file
  build/deploy config (`Dockerfile.production`, `.gitattributes`) — nằm **ngoài thang đo can thiệp core**. Ý định ghi
  "không đụng code PHP core" là đúng, nhưng ghi "mức 1" khiến người đọc tưởng PR thêm plugin.
- **Bằng chứng:**
  ```
  $ vc-dev/instructions/architecture.md:398-405
  | Mức | Cách làm | Vích dụ | Đụng core? |
  | 1 | Thêm **plugin** mới trong `plugins/` | Form liên hệ mới, cổng thanh toán | ❌ |
  | 2 | Thêm **theme** mới ... | 5 | **Create-only** ... | 6 | Sửa file core hiện có |
  ```
- **Fix:** ghi "ngoài thang đo can thiệp core — chỉ build/deploy config (`Dockerfile.production`, `.gitattributes`),
  không sửa file PHP core" (hoặc "mức 1 theo tinh thần §4.2").
- **Trạng thái (re-review 1 @ `2e80b16`):** **FIXED** — commit `2e80b16` đã sửa ở **cả plan lẫn PR body**:
  plan `vc-dev/issues/dockerfile-production-build-fail.md:52-53` ghi "**ngoài thang đo tính năng (mức 1–6)** — chỉ build/deploy
  config (`Dockerfile.production`, `.gitattributes`), không sửa file PHP core"; PR body §"Mức can thiệp core" dùng
  "Ngoài thang đo tính năng (architecture.md §4.2) — chỉ build/deploy config, không sửa code PHP core." (đã bỏ nhãn "mức 1").
  Theo dõi: **T11** — `vc-dev/issues/dockerfile-production-build-fail.md:89`. Không còn chặn merge.

### 3. MINOR — `Dockerfile` (dev) giữ nguyên pattern dễ vỡ (`USER www-data` trước step cần root) — chưa fail, nên ngoài phạm vi

- **Vị trí:** `Dockerfile:24-38` (`USER www-data` rồi `RUN mkdir -p ... && chown ... && chmod ...`) — PR **không** sửa file này
  (`git diff --name-status origin/vc-dev...HEAD -- Dockerfile` → rỗng)
- **Vấn đề:** dev image **KHÔNG fail** (khác production) nên tác giả đưa ra ngoài phạm vi là **đúng**. Cụ thể:
  `php:8.3-fpm` có `/var/www/html` là `drwxrwxrwt www-data www-data` (world-writable + sticky) nên `mkdir/chown/chmod`
  chạy dưới `www-data` đều OK; danh sách `chmod` của dev **không có `vendor`**; dev **không** có
  `COPY docker-entrypoint.sh` + `RUN chmod +x`; `COPY` đã có `--chown=www-data:www-data` và chạy **trước** `USER`.
  Tuy nhiên pattern "chạy step cần quyền root dưới user thường" vẫn là mầm lỗi: nếu sau này thêm `vendor` vào danh sách
  `chmod`, hoặc COPY một file root-owned rồi `chmod +x`, thì build dev sẽ fail y hệt production. Mức độ **thấp**:
  `docker-compose.yaml` mount source host (`./:/var/www/html/`) nên `vendor/` của dev lấy từ host, image dev không cần vendor.
- **Bằng chứng:**
  ```
  $ docker run --rm php:8.3-fpm sh -c "ls -ld /var/www/html; id www-data"
  drwxrwxrwt 2 www-data www-data 4096 /var/www/html
  uid=33(www-data) gid=33(www-data) groups=33(www-data)

  $ docker run --rm -u www-data php:8.3-fpm sh -c "mkdir -p storage/cache ... plugins config && echo MKDIR_OK && chown -R www-data:www-data ... && echo CHOWN_OK && chmod -R 775 ... && echo CHMOD775_OK && chmod -R 755 public && echo CHMOD755_OK"
  MKDIR_OK / CHOWN_OK / CHMOD775_OK / CHMOD755_OK     (exit 0)

  $ git show origin/vc-dev:Dockerfile | findstr /i "vendor composer"
  (không có kết quả — dev Dockerfile không có vendor/composer)
  ```
- **Fix:** ở PR sau, dời `USER www-data` xuống cuối `Dockerfile` (dev) giống production để loại mẫu hổ phách
  (không cần làm trong PR này).
- **Trạng thái (re-review 1 @ `2e80b16`):** **STILL OPEN** — delta `510d2f3..2e80b16` **không** đụng `Dockerfile` dev
  (đúng phạm vi: image dev không fail, xem bằng chứng ở trên). Đã có task theo dõi **T12** —
  `vc-dev/issues/dockerfile-production-build-fail.md:90` (dời `USER www-data` xuống cuối file ở PR sau). **Không chặn merge.**

### 4. MINOR — MINOR/NIT chưa có task theo dõi trong plan (điều kiện bắt buộc của §6.3)

- **Vị trí:** `vc-dev/issues/dockerfile-production-build-fail.md:75-77` (T7/T8/T9 đang `PENDING`),
  `vc-dev/issues/dockerfile-production-build-fail.md:6` (`Trạng thái tổng: IN_PROGRESS`),
  `vc-dev/issues/README.md:24` (dòng index trạng thái `IN_PROGRESS`)
- **Vấn đề:** `development.md` §6.3 quy định "MINOR/NIT để follow-up chỉ được chấp nhận nếu **đã tạo file theo dõi**
  (`vc-dev/issues/<slug>.md` hoặc task trong plan hiện tại) và **link file đó trong review record**. Không có link ⇒ coi như
  còn OPEN ⇒ không merge". File plan của PR này đã tồn tại và được link tại đây, nhưng **chưa có task nào** ghi nhận
  Finding #1–#3 ⇒ tại thời điểm review, 3 finding trên đều ở trạng thái OPEN chưa được track.
- **Bằng chứng:**
  ```
  $ grep -n "T[0-9]" vc-dev/issues/dockerfile-production-build-fail.md
  75: - [ ] PENDING: T7 — Review độc lập (subagent `general`), ghi `vc-dev/pr-review/pr-<n>-dockerfile-production-build-fail.md`
  76: - [ ] PENDING: T8 — PR vào `vc-dev` và merge
  77: - [ ] PENDING: T9 — PR `vc-dev` → `vc-prod` và merge (deploy lại trên Coolify)
  # không có task nào cho F1/F2/F3
  ```
- **Fix:** tác giả thêm vào `vc-dev/issues/dockerfile-production-build-fail.md` (tasklist + mục follow-up) các task:
  - **T10** — sửa wording Root cause #3 trong plan + PR body (F1);
  - **T11** — sửa nhãn "Mức can thiệp core" theo §4.2 (F2);
  - **T12** — đồng bộ `USER www-data` của `Dockerfile` dev (F3), ngoài phạm vi PR này;
  và đánh dấu **T7 COMPLETED** (review record đã tạo: `vc-dev/pr-review/pr-40-dockerfile-production-build-fail.md`).
  Sau khi merge: đổi trạng thái plan → `MERGED`, dòng index `vc-dev/issues/README.md` → `MERGED`, đổi tên file review
  → `completed-pr-40-dockerfile-production-build-fail.md`, cập nhật index `vc-dev/pr-review/README.md`.
- **Trạng thái (re-review 1 @ `2e80b16`):** **FIXED** — commit `2e80b16` đã thêm bảng
  `### Follow-up (từ review PR #40)` với **T10** (F1), **T11** (F2), **T12** (F3) tại
  `vc-dev/issues/dockerfile-production-build-fail.md:84-90`, và chuyển **T7 → `[x] COMPLETED`** (`:78-80`, có trích dẫn
  record `vc-dev/pr-review/pr-40-dockerfile-production-build-fail.md`). Review record này link file plan
  (10 tham chiếu tới `vc-dev/issues/dockerfile-production-build-fail.md`) ⇒ đủ điều kiện §6.3.
  Phần "bookkeeping sau merge" vẫn còn (ghi chú cho tác giả, **không chặn merge**).

## Claim đã xác minh đúng

- Claim "`chmod -R 755 ... vendor` chạy trước khi `vendor/` tồn tại ⇒ exit 1" — **ĐÚNG**:
  `git show origin/vc-dev:Dockerfile.production` cho thấy `chmod -R 755 public app config system rest graphql plugins vendor`
  ở **dòng 56**, còn `RUN composer install` ở **dòng 59** (sau) ⇒ `vendor/` chưa tồn tại lúc chmod.
  `.dockerignore:4` loại `vendor/` khỏi build context (`git check-ignore -v --no-index vendor/docker/autoload.php` → `.gitignore:37:vendor`).
  Tái hiện: `docker run --rm -u www-data php:8.3-apache sh -c 'chmod -R 755 /var/www/html/vendor; echo "chmod_exit=$?"'`
  → `chmod: cannot access '/var/www/html/vendor': No such file or directory`, `chmod_exit=1` ⇒ **khớp đúng lỗi Coolify báo**.
- Claim "`USER www-data` đặt trước step cần quyền root ⇒ `COPY docker-entrypoint.sh` tạo file root-owned ⇒ `RUN chmod +x` fail" — **ĐÚNG**:
  `origin/vc-dev:Dockerfile.production` có `USER www-data` ở **dòng 41**, `COPY docker-entrypoint.sh /usr/local/bin/` ở **dòng 72**
  (**không** có `--chown` ⇒ file root-owned), `RUN chmod +x` ở **dòng 73** (chạy dưới `www-data`).
  Tái hiện: `docker run --rm php:8.3-apache sh -c "touch /usr/local/bin/t.sh; chown root:root /usr/local/bin/t.sh; chmod 644 ...; su -s /bin/sh www-data -c 'chmod +x /usr/local/bin/t.sh'"`
  → `chmod: changing permissions of '/usr/local/bin/t.sh': Operation not permitted`.
- Claim "Không phải nguyên nhân: `mkdir`/`chown` dưới `www-data`" — **ĐÚNG**:
  `docker run --rm php:8.3-apache sh -c "ls -ld /var/www/html"` → `drwxrwxrwt www-data www-data`;
  `docker run --rm -u www-data php:8.3-apache sh -c 'mkdir -p .../storage/cache && chown -R www-data:www-data ... && chmod -R 775 ...'`
  → `mkdir_chown_exit=0`.
- Claim "Dockerfile mới: mọi step build chạy as root, `USER www-data` chuyển xuống cuối file, `chmod vendor` + `chown vendor` sau `composer install`" — **ĐÚNG**:
  `Dockerfile.production:41-56` (permission RUN, không còn `USER` trước), `:59-61`
  (`composer install ... && chown -R www-data:www-data vendor && chmod -R 755 vendor`), `:74` (COPY entrypoint),
  `:76-77` (`sed -i 's/\r$//'` + `chmod +x`), `:81` `USER www-data` là instruction cuối cùng trước
  `ENTRYPOINT` (`:83`) ⇒ đúng mô hình "build as root, runtime as www-data".
- Claim "`.gitattributes` thêm `*.sh text eol=lf`" — **ĐÚNG**: `.gitattributes:7` = `*.sh text eol=lf`;
  `git check-attr text eol -- docker-entrypoint.sh` → `text: set`, `eol: lf`; cả 3 file `.sh` trong repo
  (`build.sh`, `docker-entrypoint.sh`, `fresh-install.sh`) đều `i/lf` (không phát sinh diff renormalize).
- Claim "Dockerfile normalize CR bằng `sed -i 's/\r$//'`" — **ĐÚNG**: `Dockerfile.production:76`; trong image build từ đầu,
  `od -c` cho thấy shebang `#!/bin/bash\n` (không có `\r`) và file có quyền `-rwxr-xr-x`.
- Claim "Build thành công + container trả HTTP 200 + app ghi được `storage/`, `config/`, `public/media/`, `public/page-cache/`" — **ĐÚNG**:
  reviewer đã build lại **không cache** (`--no-cache`, context 367.06 kB, không có `vendor/` stale) → build exit 0;
  container trả `HTTP 200`; `id` → `uid=33(www-data)`; `touch storage/cache/_t`, `touch config/_t`, `touch public/media/_t`,
  `touch public/page-cache/_t` → đều OK; `ls -ld` → `vendor`, `storage`, `config` đều `www-data www-data`.
- Claim "Apache vẫn bind được port 80 dưới user `www-data`" — **ĐÚNG**: `ps -o pid,user,comm -e` cho thấy PID 1 = `www-data apache2`
  và container trả `HTTP 200` trên port 80; `curl -f http://localhost:80/` trong container → `HEALTHCHECK_OK`
  (đúng lệnh healthcheck trong `docker-compose.production.yaml:53`).
- Claim "`vendor/` thuộc `www-data` sau khi composer chạy as root" — **ĐÚNG**:
  `ls -ld /var/www/html/vendor` → `drwxr-xr-x 3 www-data www-data`; `composer.lock` cũng là file đã commit (621 byte) nên
  `composer install` cài từ lock file ⇒ build deterministic, không cần network packagist.
- Claim "Không có secret / file DB / đường dẫn cấm trong diff" — **ĐÚNG**:
  `git diff --name-status origin/vc-dev...HEAD` → 4 file (`M .gitattributes`, `M Dockerfile.production`,
  `M vc-dev/issues/README.md`, `A vc-dev/issues/dockerfile-production-build-fail.md`); **không có** `storage/**`,
  `vc-dev/wip/**`, `config/db.php`, `*.zip`, `node_modules`. Diff không chứa token/password/key.
- Claim "Plan file theo template `development.md` §4.2" — **ĐÚNG**: file có đủ 5 mục bắt buộc
  (`## Objective` + Definition of Done 4 mục, `## Scope & Impact Analysis` đủ 6 trường bắt buộc,
  `## Step-by-Step Tasklist` T1–T9 đúng cú pháp `- [x] COMPLETED:` / `- [ ] PENDING:`,
  `## Test Plan`, `## Handoff Summary` có Status Current Phase / Key Decisions / Context for Next Agent / Next Action Items);
  file kết thúc bằng LF (8141 byte, byte cuối = 10), không có emoji.
- Claim "Index `vc-dev/issues/README.md` đã cập nhật" — **ĐÚNG**: dòng 24 thêm
  `| dockerfile-production-build-fail.md | IN_PROGRESS | fix/dockerfile-production-build-permissions | ... |`;
  đồng thời dòng 25 đổi `docker-production-external-db.md` từ `IN_PROGRESS` → `MERGED` (đúng thực tế — PR #38 đã merge).
- Claim "Không sửa file nào thuộc danh sách cấm (`agent-guide.md` §2)" — **ĐÚNG**: không đụng `vendor/**`, `storage/**`,
  `public/media/**`, `public/admin/default/**`, `config/db.php`, `config/app.php`.

## Claim không xác minh được

- Claim "Coolify deploy `vc-prod` build + start thành công, healthcheck xanh" — **CHƯA XÁC MINH ĐƯỢC**: không có quyền
  truy cập instance Coolify từ máy reviewer. Reviewer chỉ dựng được stack tương đương local bằng Docker 29.5.3.
  Task T9 trong plan vẫn `PENDING` là đúng thực tế — reviewer **không** tick mục này.
- Claim "Build trên Coolify có network để `composer install`" — **CHƯA XÁC MINH ĐƯỢC**, nhưng rủi ro rất thấp: `composer.json`
  chỉ yêu cầu `php >= 7.4`, `composer.lock` đã commit ⇒ build log cho thấy "Nothing to install, update or remove"
  ⇒ **không** cần tải package nào.
- Claim "`/var/www/html` writable và `chmod` list không có `vendor` nên `Dockerfile` dev build được" — **XÁC MINH ĐƯỢC Ở MỨC CƠ CHẾ**
  (đã chạy đúng chuỗi `mkdir/chown/chmod` dưới `www-data` trong `php:8.3-fpm` → exit 0; không có `vendor`/`composer`
  trong dev Dockerfile), nhưng reviewer **không chạy full `docker build -f Dockerfile`** (dev) tới bước export vì
  thời gian compile extension không nằm trong phạm vi kiểm tra bắt buộc. Kết luận: dev Dockerfile **không** có cùng lỗi
  ⇒ tác giả không cần sửa nó trong PR này.
- Claim "Volume `vvveb_storage` / `vvveb_page_cache` trên production giữ được ownership `www-data` sau đổi Dockerfile" —
  **CHƯA XÁC MINH ĐƯỢC** trên host production: volume named volume chỉ được seed ownership từ image **khi volume còn trống**;
  nếu volume production đã tồn tại từ deploy trước thì ownership cũ được giữ nguyên. Ảnh hưởng thấp (app vẫn ghi được vì
  owner cũ cũng là `www-data` — image cũ tạo `storage` dưới user `www-data`), nhưng nên kiểm tra `ls -ld /var/www/html/storage`
  trong container production sau deploy.
- Claim "PR không ảnh hưởng upstream sync" — **ĐÚNG theo phạm vi file** (chỉ `Dockerfile.production` + `.gitattributes`,
  không phải file upstream core) nhưng reviewer không chạy thử `git merge upstream/master` ⇒ chỉ xác minh được mức
  "không đụng file core".

## Quyết định

**APPROVED WITH FOLLOW-UPS.** Build production đã được reviewer **tự build lại từ đầu** (`--no-cache`, context sạch,
không có `vendor/` stale) và **PASS**; container chạy đúng, trả `HTTP 200`, Apache master chạy dưới `www-data` vẫn bind
được port 80, `vendor/`/`storage/`/`config/` thuộc `www-data`, app ghi được `storage/`, `config/`, `public/media/`,
`public/page-cache/`. Cả 3 lỗi riêng biệt trong `Dockerfile.production` cũ được tái hiện độc lập và fix tương ứng là đúng
(2 lỗi gây fail Coolify + 1 lỗi CRLF chỉ ở build local trên Windows — xem Finding #1).
Không có `BLOCKER`/`MAJOR`; diff không đụng file cấm, không có secret; plan file đủ template §4.2 và index issue đã cập nhật.

**Re-review 1 tại `2e80b16`:** delta **docs-only**, `Dockerfile.production`/`.gitattributes` **không đổi** ⇒ nội dung deploy
y hệt bản đã duyệt. F1, F2, F4 đã **FIXED** (wording sửa trong plan + PR body; T10/T11/T12 thêm vào plan; T7 `COMPLETED`);
F3 còn **STILL OPEN** nhưng đã có task theo dõi **T12** (ngoài phạm vi PR này). ⇒ **Không còn điều kiện bắt buộc nào chặn merge.**

**Điều kiện bắt buộc trước khi merge (theo §6.3 — chỉ commit docs-only, không đụng file runtime):**

1. ~~Thêm task theo dõi vào `vc-dev/issues/dockerfile-production-build-fail.md` cho các finding `MINOR`/`NIT`:
   **T10** (F1 — sửa wording Root cause #3), **T11** (F2 — nhãn mức can thiệp theo §4.2), **T12** (F3 — đồng bộ
   `USER www-data` cho `Dockerfile` dev, ngoài phạm vi PR này); đánh dấu **T7 COMPLETED**.~~
   **ĐÃ ĐẠT (commit `2e80b16`)**: T10/T11/T12 có trong plan `:84-90`, T7 `COMPLETED` tại `:78-80`.
2. ~~Commit review record này + index `vc-dev/pr-review/README.md` vào nhánh PR hoặc `vc-dev`.~~
   **ĐÃ ĐẠT (commit `2e80b16`)**: file review record `vc-dev/pr-review/pr-40-dockerfile-production-build-fail.md` đã được
   commit (`A` trong `git diff --name-status 510d2f3..2e80b16`) và index đã có dòng PR #40
   (`vc-dev/pr-review/README.md:34`, trạng thái `READY TO MERGE`).
3. Sau khi merge: đổi trạng thái plan → `MERGED`, dòng index `vc-dev/issues/README.md` → `MERGED`, đổi tên file review
   → `completed-pr-40-dockerfile-production-build-fail.md`, cập nhật index `vc-dev/pr-review/README.md`.
4. Deploy: sau khi Coolify build xong, kiểm tra `ls -ld /var/www/html/storage /var/www/html/config` trong container và
   `/install/` trả 200 (task T9 của plan).

**Điều kiện merge (cập nhật sau re-review 1):**

- **Code (runtime/config) được review tại `510d2f32f156f5ae6129eb7207d3fa118caf0b01`** và **không đổi** tới
  `2e80b16ee0feebba78bfe7799923e9883f073f0b` (`git diff 510d2f3..2e80b16 -- Dockerfile.production .gitattributes` rỗng).
- **Commit review hiện tại:** `2e80b16ee0feebba78bfe7799923e9883f073f0b` (head PR #40 tại thời điểm re-review 1).
  Commit duy nhất sau `510d2f3` là **docs-only** và mang chính file review record này + index + sửa wording trong plan
  ⇒ đúng tiền lệ PR #13 (review record được commit docs-only ở `7df1131`, merge ở `418753d`) và PR #39.
- Có thể merge khi head của PR **là `2e80b16`**; nếu tác giả push thêm commit thì chỉ được là commit
  **docs-only bookkeeping** (chỉ đụng `vc-dev/pr-review/**` và `vc-dev/issues/**`); nếu đụng `Dockerfile.production`
  hoặc `.gitattributes` thì bắt buộc re-review delta mới theo §6.3.

## Checklist hoàn tất review (trước khi merge)

- [x] File `vc-dev/pr-review/pr-40-dockerfile-production-build-fail.md` tồn tại (đã commit ở `2e80b16`).
- [x] `Commit review` khớp `gh pr view 40 --json headRefOid` = `2e80b16ee0feebba78bfe7799923e9883f073f0b` (re-review 1);
      code runtime được review tại `510d2f32f156f5ae6129eb7207d3fa118caf0b01` và không đổi tới `2e80b16`.
- [x] Delta re-review `510d2f3..2e80b16` chỉ gồm 3 file dưới `vc-dev/` (`git diff --name-status`);
      `git diff 510d2f3..2e80b16 -- Dockerfile.production .gitattributes` **rỗng** ⇒ không file runtime nào đổi.
- [x] Đọc diff thật (`gh pr view 40 --json files,additions,deletions`, `gh pr diff 40`, `git diff origin/vc-dev...HEAD`) — 4 file.
- [x] Root cause #1 xác minh bằng lệnh thật (`.dockerignore` loại `vendor/`, `chmod ... vendor` dòng 56 **trước**
      `composer install` dòng 59 ở bản cũ; tái hiện `chmod_exit=1`).
- [x] Root cause #2 xác minh bằng lệnh thật (`USER www-data` dòng 41 **trước** `COPY docker-entrypoint.sh` dòng 72 +
      `RUN chmod +x` dòng 73; COPY không có `--chown`; tái hiện `Operation not permitted`).
- [x] Root cause #3 xác minh (`git ls-files --eol`, `.gitattributes:7`, `Dockerfile.production:76` `sed -i 's/\r$//'`,
      shebang LF trong image) — kèm lưu ý về độ chính xác của mô tả (Finding #1, đã FIXED ở `2e80b16`).
- [x] Dockerfile mới: mọi step build as root, `USER www-data` là instruction cuối trước `ENTRYPOINT` (`:81`),
      `chown -R www-data:www-data vendor` + `chmod -R 755 vendor` ngay sau `composer install` (`:59-61`).
- [x] **Build lại từ đầu bởi reviewer**: `docker build --no-cache -f Dockerfile.production -t vvveb-review-nocache:latest .`
      → thành công (context 367.06 kB, không có `vendor/` stale). *(Không build lại ở re-review 1: code runtime không đổi.)*
- [x] Runtime: `HTTP 200`, Apache PID 1 = `www-data`, `HEALTHCHECK_OK`, ghi được `storage/`, `config/`, `public/media/`,
      `public/page-cache/`; `vendor/` thuộc `www-data`; entrypoint `-rwxr-xr-x` với shebang LF.
- [x] Regression `Dockerfile` (dev, `php:8.3-fpm`): không có cùng lỗi (đã chạy chuỗi `mkdir/chown/chmod` dưới `www-data`
      → exit 0; không có `vendor`/`composer`/entrypoint-chmod) ⇒ tác giả **không** bắt buộc sửa trong PR này (Finding #3).
- [x] Repo hygiene: `git diff --name-status origin/vc-dev...HEAD` không có `storage/**`, `vc-dev/wip/**`, `config/db.php`,
      secret; plan file theo template §4.2 và kết thúc bằng LF; index `vc-dev/issues/README.md:24` đã có dòng mới.
- [x] Re-review 1: PR body đã sửa đúng — **2 nguyên nhân** (chmod vendor trước composer install; `USER www-data` trước
      step cần root), CRLF ghi rõ **không phải nguyên nhân deploy fail** (Coolify clone Linux ⇒ blob LF),
      wording "Mức can thiệp core" = "Ngoài thang đo tính năng (architecture.md §4.2) — chỉ build/deploy config,
      không sửa code PHP core".
- [x] Re-review 1: plan có **T10** (F1), **T11** (F2), **T12** (F3) và **T7 `COMPLETED`**; review record này link
      file plan `vc-dev/issues/dockerfile-production-build-fail.md` ⇒ đủ điều kiện §6.3.
- [x] Verdict cuối `APPROVED WITH FOLLOW-UPS`; không còn `BLOCKER`/`MAJOR` OPEN; không còn điều kiện bắt buộc nào chặn merge.
- [x] **Tác giả**: thêm T10/T11/T12 vào `vc-dev/issues/dockerfile-production-build-fail.md` cho F1/F2/F3 và đánh dấu
      T7 `COMPLETED` — **ĐÃ XONG** ở commit `2e80b16` (bắt buộc theo §6.3 — MINOR/NIT phải có task theo dõi).
- [ ] Sau merge: đổi trạng thái plan → `MERGED`, dòng index issues → `MERGED`, đổi tên file review →
      `completed-pr-40-dockerfile-production-build-fail.md`, cập nhật index `vc-dev/pr-review/README.md`.
- [ ] Deploy (task T9): Coolify build `vc-prod` xong, `ls -ld /var/www/html/storage /var/www/html/config` đúng owner,
      `/install/` trả 200 — **chưa xác minh được từ máy reviewer**.
