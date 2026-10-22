# PR #41 — `vc-dev` → `vc-prod` (release)

- **Reviewer:** independent session (Kilo — `kilo-auto/free`), context sạch, không phải tác giả PR
- **Ngày:** 2026-10-22
- **Base:** `vc-prod` (`02e1a19b646baa371d77f36ae94be0d98a4c6376`) · **Head:** `vc-dev` · **Commit review:** `93afdc8990234968e294c6db6a31d911a901457d`
- **PR:** #41 — "Merge vc-dev to vc-prod: fix build production Docker (#40)"
- **PR đã review trước đó:** #40 (`fix/dockerfile-production-build-permissions` → `vc-dev`) — record `vc-dev/pr-review/completed-pr-40-dockerfile-production-build-fail.md`, verdict `APPROVED WITH FOLLOW-UPS`, merge commit `b6cfe36`
- **Verdict ban đầu:** APPROVED WITH FOLLOW-UPS
- **Verdict cuối:** APPROVED WITH FOLLOW-UPS (chưa có re-review; nếu tác giả commit thêm bản ghi review này + fix F1/F3 thì bắt buộc re-review delta docs-only theo `development.md` §6.3)

## Tóm tắt

PR release đưa nội dung đã duyệt ở PR #40 lên nhánh production `vc-prod`: sửa `Dockerfile.production` để build production
Coolify không còn fail (`exit code: 1` ở step tạo quyền), thêm `*.sh text eol=lf` vào `.gitattributes`, kèm toàn bộ tài liệu
(plan `vc-dev/issues/dockerfile-production-build-fail.md`, review record PR #40 + đổi tên file sang `completed-`,
index `vc-dev/pr-review/README.md`, index `vc-dev/issues/README.md`).

Reviewer đã xác minh bằng lệnh thật:

1. **Code deploy trong PR này byte-identical với bản đã review ở PR #40** — `git diff b6cfe36 origin/vc-dev -- Dockerfile.production .gitattributes` **rỗng tuyệt đối** ⇒ nội dung deploy y hệt bản đã build `--no-cache` và chạy container ở review PR #40 ⇒ **không cần build lại image**.
2. **Không có secret / file DB / đường dẫn cấm** trong delta (8 file, toàn bộ dưới `vc-dev/` trừ 2 file build config).
3. **Plan file trung thực** — Coolify end-to-end deploy vẫn được đánh dấu `PENDING` ở cả tasklist (T9) và Test Plan; không có chỗ nào trình bày deploy production là đã verify.
4. **Review record PR #40 tồn tại** (`vc-dev/pr-review/completed-pr-40-dockerfile-production-build-fail.md`) và **PR body có trích dẫn** verdict của nó.
5. **Contract với `docker-compose.production.yaml` không bị phá** — compose vẫn trỏ `build.dockerfile: Dockerfile.production`; container vẫn chạy dưới `www-data`, Apache master vẫn bind port 80, `curl -f http://localhost:80/` (đúng lệnh healthcheck) trả exit 0, named volume `storage` / `public/page-cache` được seed đúng owner `www-data` và ghi được.

Không có `BLOCKER`/`MAJOR`. Toàn bộ finding là `MINOR`/`NIT` về **bookkeeping tài liệu** (Handoff Summary stale, index review chưa đồng bộ); theo `development.md` §6.3, mỗi finding này **cần task theo dõi trong plan file** trước khi merge (xem "Điều kiện bắt buộc").

## Bằng chứng xác minh (reviewer chạy, không tin PR body)

### 1. Delta = đúng nội dung PR #40 đã duyệt + tài liệu

```
$ git log --oneline origin/vc-prod..origin/vc-dev
93afdc8 docs(vc-dev): rename review PR #40 thanh completed + cap nhat index va plan sau merge
b6cfe36 Merge pull request #40 from honggiang13/fix/dockerfile-production-build-permissions
35b93e6 docs(pr-review): re-review PR #40 - APPROVED WITH FOLLOW-UPS
2e80b16 docs(vc-dev): review doc lap PR #40 + sua mo ta root cause + follow-up T10-T12
510d2f3 fix(docker): sua build production fail - chmod vendor, USER www-data, CRLF shebang
17d7dd5 docs(vc-dev): dong plan docker-production-external-db - MERGED, T8 xong
bce4a53 chore: sync vc-prod into vc-dev (PR #39 release merge)
881c8c9 docs(vc-dev): rename review PR #39 thanh completed + cap nhat index va plan sau merge

$ git merge-base origin/vc-prod origin/vc-dev
02e1a19b646baa371d77f36ae94be0d98a4c6376        # = origin/vc-prod ⇒ diff 3 chấm == diff 2 chấm

$ git diff --name-status origin/vc-prod...origin/vc-dev
M       .gitattributes
M       Dockerfile.production
M       vc-dev/issues/README.md
M       vc-dev/issues/docker-production-external-db.md
A       vc-dev/issues/dockerfile-production-build-fail.md
M       vc-dev/pr-review/README.md
R100    vc-dev/pr-review/pr-39-release-vc-prod-docker-external-db.md  ->  vc-dev/pr-review/completed-pr-39-release-vc-prod-docker-external-db.md
A       vc-dev/pr-review/completed-pr-40-dockerfile-production-build-fail.md

$ git diff b6cfe36 origin/vc-dev -- Dockerfile.production .gitattributes
(rong — 0 dong, exit code 0)
```

`b6cfe36` là merge commit của PR #40 (code runtime + docs), `93afdc8` chỉ là commit docs rename file review + cập nhật index/plan.
⇒ **Không có thay đổi nào đối với image production so với bản đã build `--no-cache` ở review PR #40.**

Đối chiếu chéo phía GitHub (không chỉ dựa vào git local):

```
$ gh pr diff 41 --name-only
.gitattributes
Dockerfile.production
vc-dev/issues/README.md
vc-dev/issues/docker-production-external-db.md
vc-dev/issues/dockerfile-production-build-fail.md
vc-dev/pr-review/README.md
vc-dev/pr-review/completed-pr-39-release-vc-prod-docker-external-db.md
vc-dev/pr-review/completed-pr-40-dockerfile-production-build-fail.md
$ gh pr view 41 --json files --jq '.files[].path'
(giong het 8 file o tren)
```

### 2. Repo hygiene — không secret / không file DB / không đường dẫn cấm

```
$ git diff --name-status origin/vc-prod...origin/vc-dev | Select-String -Pattern 'storage/|\.db$|wip/|config/db\.php|\.env'
(khong co ket qua)

$ git diff origin/vc-prod...origin/vc-dev | Select-String -Pattern '(?i)(password|passwd|secret|api[_-]?key|token|BEGIN [A-Z ]*PRIVATE KEY|DB_PASSWORD|AKIA[0-9A-Z]{16}|ghp_[A-Za-z0-9]{20,})' -Context 1,1
(chi trung tu "secret" trong van xuoi cua review record PR #40; khong co gia tri khoa/ token)

$ git diff origin/vc-prod...origin/vc-dev | Select-String -Pattern '(?i)(ignore (all )?previous|disregard|system prompt|you are now|jailbreak|exfiltrat)'
(khong co ket qua — khong co noi dung chen instruction trong diff/PR body)
```

Không đụng danh sách cấm của `agent-guide.md` §2 (`storage/**`, `config/db.php`, `public/admin/default/**`,
`public/themes/landing/**`, `public/themes/blog-default/**`, `plugins/{contact-form,payment,shipping}/**`,
`public/js/libs/tinymce-dist/**`, `*.zip`). Delta không chứa `storage/sqlite/vvveb.db` (file này đang dirty sẵn ở
working tree local nhưng **không** nằm trong delta của PR).

### 3. Plan file — đủ template §4.2 và trung thực

```
$ git show origin/vc-dev:vc-dev/issues/dockerfile-production-build-fail.md
```

- `## Objective` + Definition of Done 4 mục — có mục 4 nêu rõ "PR vào `vc-dev`, sau đó PR `vc-dev` → `vc-prod`".
- `## Scope & Impact Analysis` — đủ 6 trường bắt buộc (module/file, mức can thiệp core, giải pháp bảo vệ core, rủi ro & giảm thiểu, ảnh hưởng sync upstream, ngoài phạm vi).
- `## Step-by-Step Tasklist` — T1–T8 `[x] COMPLETED`, T9 `[ ] PENDING` (`:82`: "PR `vc-dev` → `vc-prod` và merge (deploy lại trên Coolify)") ⇒ **đúng thực tế**, PR #40 đã merge (`b6cfe36`), PR #41 đang review.
- `## Test Plan` — 5 mục `COMPLETED` (đều có bằng chứng lệnh + kết quả), 1 mục `PENDING` (`:105`): "Coolify deploy `vc-prod` build + start thành công, healthcheck xanh" ⇒ **không có chỗ nào trình bày deploy production là đã verify**.
- `## Handoff Summary` — có Status Current Phase / Key Decisions & Findings / Context for Next Agent / Next Action Items (xem Finding #1: phần này stale so với tasklist).
- File kết thúc bằng LF (`lastByte=10`, 10030 byte), không có emoji.

PR body cũng trung thực: mục "## Test đã chạy (local, Docker 29.5.3)" để **ô `[ ]` trống** cho mục
"Coolify deploy `vc-prod` build + start thành công, healthcheck xanh" ⇒ không tuyên bố deploy production đã pass.

### 4. Review record PR #40 tồn tại và được PR body trích dẫn

```
$ git ls-tree origin/vc-dev -- vc-dev/pr-review/ | Select-String "pr-40"
100644 blob 9c2f7c... vc-dev/pr-review/completed-pr-40-dockerfile-production-build-fail.md

$ gh pr view 41 --json body
... "Plan: `vc-dev/issues/dockerfile-production-build-fail.md` · Review: `vc-dev/pr-review/completed-pr-40-dockerfile-production-build-fail.md`
    (verdict `APPROVED WITH FOLLOW-UPS`, không có `BLOCKER`/`MAJOR`)."
```

Record PR #40 nội dung đầy đủ theo §6.3 (reviewer + ngày + Commit review + verdict + findings + claim đã/không xác minh được + re-review 1 + checklist) — không có `BLOCKER`/`MAJOR` OPEN.

### 5. Contract với `docker-compose.production.yaml` — không bị phá

Compose file **không bị thay đổi** trong delta (không có trong danh sách 8 file) ⇒ contract giữ nguyên:

```
$ docker compose -f docker-compose.production.yaml config
(trich doan - boi cac bien moi truong DB_*/APP_KEY/CRON_KEY va phan network)
name: vvveb
services:
  php:
    build:
      context: E:\Personal\VCenter\vcenter-vvveb\Vvveb
      dockerfile: Dockerfile.production          <- van tro dung Dockerfile.production
    container_name: vvveb-app
    environment:
      APACHE_RUN_GROUP: www-data
      APACHE_RUN_USER: www-data
    healthcheck:
      test:
        - CMD-SHELL
        - curl -f http://localhost:80/ || exit 1
      timeout: 10s
      interval: 30s
      retries: 3
      start_period: 20s
    labels:
      traefik.enable: "true"
      traefik.http.services.vvveb.loadbalancer.server.port: "80"
    volumes:
      - type: volume
        source: vvveb_storage
        target: /var/www/html/storage
      - type: volume
        source: vvveb_page_cache
        target: /var/www/html/public/page-cache
```

Image local `vvveb-prod-test:latest` (đã build từ **đúng** nội dung `Dockerfile.production` sau fix — xác minh bằng `docker history`)
được dùng để kiểm chứng runtime, **không build lại** vì nội dung file đã chứng minh byte-identical (mục 1):

```
$ docker history vvveb-prod-test:latest --no-trunc --format "{{.CreatedBy}}" | Select-String "chmod|composer|sed|USER"
USER www-data
RUN /bin/sh -c sed -i 's/\r$//' /usr/local/bin/docker-entrypoint.sh     && chmod +x /usr/local/bin/docker-entrypoint.sh
COPY docker-entrypoint.sh /usr/local/bin/ # buildkit
RUN /bin/sh -c composer install --no-dev --optimize-autoloader --no-interaction     && chown -R www-data:www-data vendor     && chmod -R 755 vendor # buildkit
RUN /bin/sh -c mkdir -p     storage/cache     storage/compiled-templates ...     && chown -R www-data:www-data ...     && chmod -R 775 ...     && chmod -R 755 public app config system rest graphql plugins # buildkit
=> khop 1-1 voi Dockerfile.production:59-61 (composer + chown/chmod vendor) va :76-81 (sed + USER www-data)

$ docker inspect vvveb-prod-test:latest --format 'User={{.Config.User}} Entrypoint={{json .Config.Entrypoint}} Cmd={{json .Config.Cmd}} ExposedPorts={{json .Config.ExposedPorts}}'
User=www-data Entrypoint=["docker-entrypoint.sh"] Cmd=["apache2-foreground"] ExposedPorts={"80/tcp":{}}
```

Runtime (container `vvveb-test` chạy từ image trên, port host `18081 -> 80/tcp`):

```
$ docker exec vvveb-test id
uid=33(www-data) gid=33(www-data) groups=33(www-data)

$ docker exec vvveb-test bash -c "ps -o pid,user,comm -e | head -3"
    PID USER     COMMAND
      1 www-data apache2        <- Apache master chay duoi www-data va van bind port 80
     25 www-data apache2

$ docker exec vvveb-test curl -f -s -o NUL http://localhost:80/; echo curl_exit=$LASTEXITCODE
curl_exit=0                     <- dung lenh healthcheck trong compose ("curl -f http://localhost:80/ || exit 1")
                                  (HTTP 302 -> /install/ khi chua co config/db.php; curl -f van exit 0)

$ docker exec vvveb-test bash -c "touch storage/cache/_r41 && echo W_STORAGE_OK; touch config/_r41 && echo W_CONFIG_OK; touch public/media/_r41 && echo W_MEDIA_OK; touch public/page-cache/_r41 && echo W_PAGECACHE_OK; rm -f ..."
W_STORAGE_OK
W_CONFIG_OK
W_MEDIA_OK
W_PAGECACHE_OK

$ docker exec vvveb-test bash -c "stat -c '%a %U:%G %n' /usr/local/bin/docker-entrypoint.sh; head -c 20 /usr/local/bin/docker-entrypoint.sh | od -c | head -2; ls -ld /var/www/html/vendor /var/www/html/storage /var/www/html/config /var/www/html/public/media"
755 root:root /usr/local/bin/docker-entrypoint.sh
0000000   #   !   /   b   i   n   /   b   a   s   h  \n   s   e   t      <- shebang LF, khong co CR
drwxr-xr-x 1 www-data www-data /var/www/html/config
drwxr-xr-x 1 www-data www-data /var/www/html/public/media
drwxr-xr-x 1 www-data www-data /var/www/html/storage
drwxr-xr-x 3 www-data www-data /var/www/html/vendor
```

Named volume (mô phỏng đúng `volumes:` của compose — tạo volume trống rồi mount vào đúng 2 target):

```
$ docker run -d --name vvveb-vol-check -v vvveb_check_storage:/var/www/html/storage -v vvveb_check_page_cache:/var/www/html/public/page-cache vvveb-prod-test:latest
$ docker exec vvveb-vol-check bash -c "ls -ld /var/www/html/storage /var/www/html/public/page-cache; id; touch /var/www/html/storage/_t && echo W_STORAGE_OK; touch /var/www/html/public/page-cache/_t && echo W_PAGECACHE_OK"
drwxr-xr-x 2 www-data www-data 4096 /var/www/html/public/page-cache
drwxr-xr-x 5 www-data www-data 4096 /var/www/html/storage
uid=33(www-data) gid=33(www-data) groups=33(www-data)
W_STORAGE_OK
W_PAGECACHE_OK
```

⇒ volume được seed từ image với đúng owner `www-data`, user runtime ghi được ⇒ contract `volumes` của compose không bị phá bởi việc chuyển `USER www-data` xuống cuối file (user runtime **không đổi** so với bản `vc-prod` trước đó: bản cũ cũng có `USER www-data`).

Kết luận mục 5: `build.dockerfile` đúng, `EXPOSE 80` + `EXPOSE`/Traefik label port 80 khớp, healthcheck chạy được với `curl` có sẵn trong image (`php:8.3-apache` base có `/usr/bin/curl`), 2 named volume mount đúng target và đúng owner.

## Findings

### 1. MINOR — `## Handoff Summary` của plan file stale so với tasklist (T7/T8 đã xong nhưng vẫn ghi "Còn lại")

- **Vị trí:** `vc-dev/issues/dockerfile-production-build-fail.md:111` (`### Status Current Phase`) và `:136-140` (`### Next Action Items`)
- **Vấn đề:** tasklist đã cập nhật đúng (`T7` review độc lập `[x] COMPLETED` tại `:78-80`, `T8` PR #40 merge `[x] COMPLETED` tại `:81`), nhưng `### Status Current Phase` vẫn ghi
  "Đã xong: T1, T2, T3, T4, T5, T6 · **Còn lại: T7, T8, T9**", và `### Next Action Items` vẫn liệt kê "1. Review độc lập PR (T7) — subagent `general`, context sạch." và
  "2. PR vào `vc-dev` (T8) rồi PR `vc-dev` → `vc-prod` (T9)" ⇒ người đọc tiếp nhận sẽ tưởng phải review/merge PR #40 lần nữa.
  `### Context for Next Agent` cũng không nêu T9 = PR #41 release hiện tại.
  Vi phạm `development.md` §4.3 rule 4 ("Mỗi lần kết thúc phiên làm việc: cập nhật ... và Handoff Summary").
  Không ảnh hưởng runtime/deploy — chỉ là bookkeeping tài liệu.
- **Bằng chứng:**
  ```
  $ git show origin/vc-dev:vc-dev/issues/dockerfile-production-build-fail.md | Select-String -Pattern "T7 —|T8 —|T9 —|Đã xong:|Còn lại:|Review độc lập PR \(T7\)|PR vào `vc-dev` \(T8\)"
  78: - [x] COMPLETED: T7 — Review độc lập PR #40 bằng subagent `general` (context sạch): verdict
  81: - [x] COMPLETED: T8 — PR #40 (`fix/dockerfile-production-build-permissions` → `vc-dev`) đã merge (merge commit `b6cfe36`) (2026-10-22)
  82: - [ ] PENDING: T9 — PR `vc-dev` → `vc-prod` và merge (deploy lại trên Coolify)
  111: - Phase: fix build production — Đã xong: T1, T2, T3, T4, T5, T6 · Còn lại: T7, T8, T9
  138: 1. Review độc lập PR (T7) — subagent `general`, context sạch.
  139: 2. PR vào `vc-dev` (T8) rồi PR `vc-dev` → `vc-prod` (T9) — `gh pr merge <n> --admin --merge`.
  ```
- **Fix:** sửa `### Status Current Phase` thành "Đã xong: T1–T8 · Còn lại: T9 (PR `vc-dev` → `vc-prod`, đang review — PR #41)"; sửa `### Next Action Items` chỉ còn "1. Merge PR #41 (`vc-dev` → `vc-prod`) sau khi có review record; 2. Deploy Coolify và xác nhận healthcheck"; bổ sung `### Context for Next Agent` nêu T9 = PR #41.
- **Trạng thái:** **OPEN** — cần **task theo dõi** trong `vc-dev/issues/dockerfile-production-build-fail.md` (tác giả thêm dòng, ví dụ **T13**) trước khi merge, theo `development.md` §6.3 ("MINOR/NIT để follow-up chỉ được chấp nhận nếu đã tạo file theo dõi ... và link file đó trong review record"). Không chặn merge về mặt kỹ thuật.

### 2. NIT — PR body mục "## Plan file" mô tả Handoff Summary là đã đầy đủ, không khớp thực tế

- **Vị trí:** PR #41 body, mục cuối "## Plan file": "`vc-dev/issues/dockerfile-production-build-fail.md` — tasklist + Handoff Summary <dấu tích> (T9: PR này)"
- **Vấn đề:** câu này khẳng định Handoff Summary đã hoàn tất, trong khi `### Status Current Phase` / `### Next Action Items` còn stale (Finding #1) ⇒ gây hiểu nhầm cho agent tiếp nhận. (Ký hiệu dấu tích trong mẫu PR template `development.md` §6.1 là chấp nhận được, không tính là finding.)
- **Bằng chứng:** xem Finding #1 (cùng bằng chứng về trạng thái T7/T8/T9 trong tasklist và phần Handoff Summary còn ghi T7/T8 là "Còn lại").
- **Fix:** sau khi sửa Finding #1, đổi câu này thành: "`vc-dev/issues/dockerfile-production-build-fail.md` — tasklist T1–T8 `COMPLETED`, T9 = PR này (`vc-dev` → `vc-prod`); Coolify deploy còn `PENDING`."
- **Trạng thái:** **OPEN** — gộp chung **task theo dõi** với Finding #1 (cùng một commit docs-only trong plan/PR body; có thể dùng chung **T13**).

### 3. NIT — Index `vc-dev/pr-review/README.md` còn 5 dòng của PR đã MERGED nhưng ghi "READY TO MERGE"/"REVIEWED"

- **Vị trí:** `vc-dev/pr-review/README.md:27-31` (PR #12, #13, #14, #20, #21)
- **Vấn đề:** rule số 3 của chính file này quy định "Sau khi PR merge, đổi tên file review thành `completed-pr-<number>-<slug>.md` và cập nhật index". Thực tế 5 PR này đã merge trên GitHub nhưng file review vẫn tên `pr-<n>-...md` và index vẫn ghi `REVIEWED` / `READY TO MERGE` ⇒ index không phản ánh trạng thái thật. Đây là **vấn đề có sẵn từ trước**, không do PR #41 gây ra, và nằm ngoài delta của PR này ⇒ reviewer **không** tự sửa trong PR release.
- **Bằng chứng:**
  ```
  $ foreach ($n in 12,13,14,20,21) { $j = gh pr view $n --json number,state,mergedAt | ConvertFrom-Json; "PR#$($j.number) state=$($j.state) mergedAt=$($j.mergedAt)" }
  PR#12 state=MERGED mergedAt=2026-10-21T15:18:37Z
  PR#13 state=MERGED mergedAt=2026-10-21T15:31:40Z
  PR#14 state=MERGED mergedAt=2026-10-21T15:52:43Z
  PR#20 state=MERGED mergedAt=2026-10-22T02:25:08Z
  PR#21 state=MERGED mergedAt=2026-10-22T02:28:33Z
  $ git ls-tree origin/vc-dev -- vc-dev/pr-review/ | Select-String "pr-12|pr-13|pr-14|pr-20|pr-21"
  (chi co entry dang pr-<n>-...md; khong co completed-<n>-...md tuong ung)
  ```
- **Fix:** đổi tên 5 file review sang `completed-pr-<n>-<slug>.md` và cập nhật 5 dòng index (`Trạng thái` → `MERGED`, `File` → tên mới), thực hiện trong PR riêng sau PR #41.
- **Trạng thái:** **OPEN** (ngoài phạm vi PR này) — cần **task theo dõi**; có thể thêm dòng vào plan `vc-dev/issues/dockerfile-production-build-fail.md` (ví dụ **T14**) hoặc tạo file theo dõi riêng `vc-dev/issues/pr-review-index-hygiene.md` và link file đó trong review record này (theo `development.md` §6.3).

## Claim đã xác minh đúng

- Claim "Nội dung chính của PR #40: sửa `Dockerfile.production` để build production Coolify không còn fail" và "`Dockerfile.production` + `.gitattributes` là toàn bộ phần runtime trong PR này" — **ĐÚNG**:
  `git diff --name-status origin/vc-prod...origin/vc-dev` cho thấy đúng 8 file, trong đó chỉ 2 file ảnh hưởng build/runtime
  (`Dockerfile.production`, `.gitattributes`); 6 file còn lại đều dưới `vc-dev/` (docs).
- Claim "Code ship trong PR này giống hệt bản đã review ở PR #40" — **ĐÚNG**:
  `git diff b6cfe36 origin/vc-dev -- Dockerfile.production .gitattributes` **rỗng** (0 dòng, exit 0); commit `93afdc8` chỉ
  rename file review + cập nhật index/plan ⇒ **không cần build lại image**, nội dung deploy y hệt bản đã build `--no-cache` ở review PR #40.
- Claim "Root cause: (1) `chmod ... vendor` chạy trước `composer install`; (2) `USER www-data` đặt trước step cần root" — **ĐÚNG**:
  diff cho thấy bản cũ (`vc-prod`) có `USER www-data` ở dòng 41 và `chmod -R 755 ... vendor` ở dòng 56 (trước `RUN composer install` ở dòng 59);
  bản mới bỏ `USER` khỏi giữa file, `chmod vendor` + `chown vendor` nằm ngay sau `composer install` (`:59-61`), `USER www-data` là instruction cuối trước `ENTRYPOINT` (`:81`).
  Cơ chế đã được tái hiện độc lập ở review PR #40 (bằng chứng lưu trong `completed-pr-40-dockerfile-production-build-fail.md`).
- Claim "`.gitattributes`: thêm `*.sh text eol=lf`" — **ĐÚNG**: `.gitattributes:7` = `*.sh text eol=lf`;
  `git check-attr text eol -- docker-entrypoint.sh build.sh fresh-install.sh` → cả 3 đều `text: set`, `eol: lf`;
  `git ls-files --eol -- '*.sh'` → cả 3 đều `i/lf` ⇒ không phát sinh diff renormalize hàng loạt.
- Claim "Build local thành công" — **ĐÚNG** (gián tiếp nhưng đủ): image local `vvveb-prod-test:latest` có `docker history` khớp 1-1 với
  `Dockerfile.production` sau fix (`composer install ... && chown -R www-data:www-data vendor && chmod -R 755 vendor`, `sed -i 's/\r$//'`, `USER www-data`);
  reviewer chạy được container từ image đó (Apache PID 1 = `www-data`, trả HTTP, ghi được thư mục runtime) ⇒ build artifact đúng với nội dung PR.
- Claim "Container chạy: entrypoint OK, `curl -f localhost:80` → HEALTHCHECK_OK" — **ĐÚNG**: `docker exec vvveb-test curl -f -s -o NUL http://localhost:80/` → `curl_exit=0`
  (đúng lệnh healthcheck `curl -f http://localhost:80/ || exit 1` trong compose); `/usr/bin/curl` có sẵn trong image ⇒ healthcheck không gãy.
- Claim "App (uid 33 `www-data`) ghi được `storage/`, `config/`, `public/media/`, `public/page-cache/`" — **ĐÚNG**: `docker exec ... id` → `uid=33(www-data)`;
  4 lệnh `touch` đều trả `W_*_OK`.
- Claim "`vendor/`, `storage/`, `config/` đều thuộc `www-data`" — **ĐÚNG**: `ls -ld` trong container → `drwxr-xr-x ... www-data www-data` cho cả 3.
- Claim "Không có secret / file DB / đường dẫn cấm trong delta" — **ĐÚNG**: xem mục "Bằng chứng xác minh" mục 2 (không có `storage/**`, `vc-dev/wip/**`, `config/db.php`, `*.db`, `.env`; không có token/password/key; không có nội dung chèn instruction).
- Claim "Plan file có tasklist + Handoff Summary" và "Coolify deploy **chưa** verify" — **ĐÚNG**: xem mục 3 (T9 `PENDING`, Test Plan `:105` `PENDING`, PR body để ô `[ ]` trống) ⇒ PR **không** trình bày deploy production là đã pass.
- Claim "Review: `vc-dev/pr-review/completed-pr-40-dockerfile-production-build-fail.md` (verdict `APPROVED WITH FOLLOW-UPS`, không có `BLOCKER`/`MAJOR`)" — **ĐÚNG**:
  file tồn tại trong `vc-dev/pr-review/` tại `origin/vc-dev`; nội dung có `Verdict ban đầu`/`Verdict cuối` = `APPROVED WITH FOLLOW-UPS`, 4 finding (1 FIXED, 1 FIXED, 1 STILL OPEN + T12, 1 FIXED) — không có `BLOCKER`/`MAJOR` OPEN; PR body có trích dẫn đúng tên file và verdict.
- Claim "Plan file theo template `development.md` §4.2" — **ĐÚNG**: đủ 5 mục bắt buộc (`## Objective` + DoD, `## Scope & Impact Analysis` đủ 6 trường, `## Step-by-Step Tasklist` đúng cú pháp trạng thái, `## Test Plan`, `## Handoff Summary` đủ 4 khối con); file kết thúc bằng LF, không có emoji.
- Claim "Index `vc-dev/issues/README.md` đã cập nhật" — **ĐÚNG**: thêm dòng `dockerfile-production-build-fail.md | IN_PROGRESS | fix/dockerfile-production-build-permissions | ...`
  và đổi `docker-production-external-db.md` từ `IN_PROGRESS` → `MERGED` (đúng thực tế — PR #39 đã merge `02e1a19`).
- Claim "Không sửa file nào thuộc danh sách cấm (`agent-guide.md` §2)" — **ĐÚNG**: delta chỉ có `.gitattributes`, `Dockerfile.production` và file tài liệu dưới `vc-dev/`.
- Claim "`docker-compose.production.yaml` không bị đụng" — **ĐÚNG**: file này không có trong `git diff --name-status origin/vc-prod...origin/vc-dev`;
  `docker compose -f docker-compose.production.yaml config` render ra `dockerfile: Dockerfile.production`, healthcheck và 2 named volume đúng target.

## Claim không xác minh được

- Claim "Coolify deploy `vc-prod` build + start thành công, healthcheck xanh" — **CHƯA XÁC MINH ĐƯỢC**: reviewer không có quyền truy cập
  instance Coolify từ máy review. Reviewer chỉ dựng được stack tương đương local (Docker 29.5.3) và xác minh được contract của compose
  (build target, healthcheck command, volumes, user runtime, port 80). Task T9 và mục Test Plan tương ứng vẫn `PENDING` là **đúng thực tế**;
  reviewer **không** tick mục này. Sau khi merge cần xác nhận trên Coolify: build xong, `/install/` trả 200, `ls -ld /var/www/html/storage /var/www/html/config`
  đúng owner `www-data`.
- Claim "Build trên Coolify có network cho `composer install`" — **CHƯA XÁC MINH ĐƯỢC**, rủi ro rất thấp: `composer.lock` đã commit và log build cho thấy
  "Nothing to install, update or remove" ⇒ không cần tải package nào.
- Claim "Named volume production (`vvveb_storage`, `vvveb_page_cache`) giữ owner `www-data` sau deploy" — **XÁC MINH ĐƯỢC Ở MỨC CƠ CHẾ**:
  reviewer dựng volume trống rồi mount vào đúng 2 target ⇒ volume được seed từ image với owner `www-data` và user runtime ghi được.
  Chưa xác minh được trên host production: nếu volume production đã tồn tại từ lần deploy trước thì ownership cũ được giữ nguyên (rủi ro thấp —
  image cũ cũng tạo `storage` dưới user `www-data`).
- Claim "PR không ảnh hưởng upstream sync" — **ĐÚNG theo phạm vi file** (chỉ `Dockerfile.production` + `.gitattributes`, không phải file upstream core);
  reviewer không chạy thử `git merge upstream/master` ⇒ chỉ xác minh được mức "không đụng file core".
- Claim "Ảnh hưởng khi deploy: cần `DB_HOST`/`DB_PASSWORD`" (mục "## Bắt buộc khi deploy (Coolify)" của PR body) — **KHÔNG XÁC MINH ĐƯỢC**:
  phụ thuộc cấu hình Coolify Variables ngoài repo; nội dung này kế thừa từ PR #39 và nhất quán với `docker-compose.production.yaml`.

## Quyết định

**APPROVED WITH FOLLOW-UPS.**

Cơ sở:

1. **Nội dung deploy đã được duyệt ở PR #40 và không đổi một byte** — `git diff b6cfe36 origin/vc-dev -- Dockerfile.production .gitattributes` rỗng;
   record `vc-dev/pr-review/completed-pr-40-dockerfile-production-build-fail.md` (`APPROVED WITH FOLLOW-UPS`, không có `BLOCKER`/`MAJOR`) là bằng chứng hợp lệ,
   và PR body có trích dẫn đúng.
2. **Không có `BLOCKER`/`MAJOR`** — không có secret, không có file DB, không đụng đường dẫn cấm, không có nội dung chèn instruction trong diff/PR body.
3. **Plan file trung thực** — Coolify deploy vẫn `PENDING` ở tasklist (T9) và Test Plan; PR body không tuyên bố deploy production đã pass.
4. **Contract với compose không bị phá** — `build.dockerfile` vẫn là `Dockerfile.production`; container chạy dưới `www-data` (không đổi so với bản `vc-prod`),
   Apache master vẫn bind port 80, lệnh healthcheck `curl -f http://localhost:80/` trả exit 0, 2 named volume seed đúng owner và ghi được.

**3 finding đều là `MINOR`/`NIT` về bookkeeping tài liệu**, không ảnh hưởng runtime. Theo `development.md` §6.3, finding `MINOR`/`NIT` được
chấp nhận dưới dạng follow-up **chỉ khi đã có task theo dõi** ⇒ **các finding sau BẮT BUỘC có task theo dõi trong
`vc-dev/issues/dockerfile-production-build-fail.md` trước khi merge** (tác giả thêm dòng, không cần sửa code):

| Finding | Severity | Task theo dõi cần thêm vào plan | Nội dung |
|---|---|---|---|
| #1 | MINOR | **T13** (bắt buộc) | Đồng bộ `## Handoff Summary` (Status Current Phase + Next Action Items + Context for Next Agent) với tasklist T1–T8 `COMPLETED`, T9 = PR #41 |
| #2 | NIT | **T13** (dùng chung với #1) | Sửa mục "## Plan file" trong PR body cho khớp thực tế (bỏ claim Handoff Summary đã đầy đủ; nêu rõ Coolify deploy còn `PENDING`) |
| #3 | NIT | **T14** (bắt buộc) — hoặc file theo dõi riêng `vc-dev/issues/pr-review-index-hygiene.md` và link file đó trong review record này | Đổi tên 5 file review đã merge (`pr-12/pr-13/pr-14/pr-20/pr-21` → `completed-*`) và cập nhật 5 dòng index `vc-dev/pr-review/README.md` → `MERGED` |

**Điều kiện bắt buộc trước khi merge (theo `development.md` §6.3):**

1. Tác giả thêm **T13** (F1 + F2) và **T14** (F3) vào `vc-dev/issues/dockerfile-production-build-fail.md` — hoặc link file theo dõi riêng cho F3.
2. Tác giả commit bản ghi review này (`vc-dev/pr-review/pr-41-release-vc-prod-build-fix.md`) + cập nhật index `vc-dev/pr-review/README.md` (dòng PR #41) vào nhánh PR.
3. **Bất kỳ commit nào mới sau `93afdc8` ⇒ bắt buộc re-review delta theo §6.3.** Vì delta sau lần review này chỉ có thể là **docs-only**
   (plan + PR body + review record + index) nên **không cần build lại image** (`Dockerfile.production`/`.gitattributes` phải giữ nguyên —
   nếu chúng đổi thì phải build lại `--no-cache` và chạy container từ đầu). Reviewer sẽ cập nhật `Commit review` / `Verdict cuối` trong file này
   sau khi xác minh delta.
4. Merge bằng `gh pr merge 41 --admin --merge` **chỉ khi** `gh pr view 41 --json headRefOid --jq .headRefOid` **bằng** `Commit review` ở file review này.
5. Sau khi merge: đổi trạng thái plan `vc-dev/issues/dockerfile-production-build-fail.md` → `MERGED`, dòng index `vc-dev/issues/README.md` → `MERGED`,
   đổi tên file review → `completed-pr-41-release-vc-prod-build-fix.md`, cập nhật index `vc-dev/pr-review/README.md`.

## Checklist hoàn tất review (trước khi merge)

- [x] File `vc-dev/pr-review/pr-41-release-vc-prod-build-fix.md` tồn tại (đang chờ tác giả commit vào nhánh PR — chưa có trong `origin/vc-dev` tại thời điểm review).
- [x] `Commit review` = `93afdc8990234968e294c6db6a31d911a901457d` = `gh pr view 41 --json headRefOid --jq .headRefOid` (head hiện tại của PR).
- [x] Verdict cuối `APPROVED WITH FOLLOW-UPS`; không có `BLOCKER`/`MAJOR` OPEN.
- [ ] Follow-up `MINOR`/`NIT` đã có task theo dõi: **T13** (F1 + F2) và **T14** (F3) trong `vc-dev/issues/dockerfile-production-build-fail.md` — **tác giả thêm dòng**
      (file plan này được link trong review record này; F3 có thể dùng file theo dõi riêng `vc-dev/issues/pr-review-index-hygiene.md` nếu tác giả thấy phù hợp hơn).
- [x] Index `vc-dev/pr-review/README.md` đã cập nhật — reviewer thêm dòng PR #41 trong file này (tác giả commit).
- [x] `git diff b6cfe36 origin/vc-dev -- Dockerfile.production .gitattributes` **rỗng** ⇒ code deploy byte-identical với bản đã review ở PR #40.
- [x] `git diff --name-status origin/vc-prod...origin/vc-dev` = 8 file; không có `storage/**`, `vc-dev/wip/**`, `config/db.php`, `*.db`, `*.zip`, `node_modules`;
      không có secret/token/key; không có nội dung chèn instruction trong diff hoặc PR body.
- [x] Plan file `vc-dev/issues/dockerfile-production-build-fail.md` đủ template §4.2 (Objective + DoD, Scope & Impact Analysis, Tasklist, Test Plan, Handoff Summary),
      kết thúc bằng LF, không có emoji; **Coolify deploy vẫn `PENDING`** ở T9 và Test Plan `:105` (không tuyên bố sai là đã verify).
- [x] Review record PR #40 tồn tại (`vc-dev/pr-review/completed-pr-40-dockerfile-production-build-fail.md`) và PR body trích dẫn đúng tên file + verdict.
- [x] Compose contract: `build.dockerfile = Dockerfile.production`, healthcheck `curl -f http://localhost:80/ || exit 1` chạy được (exit 0), user runtime `www-data`
      (không đổi so với `vc-prod`), Apache master bind port 80, 2 named volume seed đúng owner `www-data` và ghi được; `EXPOSE 80` + label Traefik port 80 khớp.
- [x] Không cần build lại image: nội dung `Dockerfile.production` không đổi so với bản đã build `--no-cache` ở review PR #40 (xác minh qua `docker history` của image local).
- [ ] Sau merge: đổi trạng thái plan → `MERGED`, dòng index `vc-dev/issues/README.md` → `MERGED`, đổi tên file review → `completed-pr-41-release-vc-prod-build-fix.md`,
      cập nhật index `vc-dev/pr-review/README.md`.
- [ ] Deploy (task T9): Coolify build `vc-prod` xong, `/install/` trả 200, healthcheck xanh, `ls -ld /var/www/html/storage /var/www/html/config` đúng owner `www-data`
      — **chưa xác minh được từ máy reviewer**.
