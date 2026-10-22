# PR #39 — `vc-dev` → `vc-prod` (release PR của #38)

- **Reviewer:** independent session (Kilo — `kilo-auto/free`), context sạch, không phải tác giả PR
- **Ngày:** 2026-10-22
- **Base:** `vc-prod` (`d0dde4e`) · **Head:** `vc-dev` (`147fe54`) · **Commit review:** `147fe54af89c937e3c71703cebc1c883bd8ce019`
- **PR:** #39 — "Merge vc-dev to vc-prod: production dùng MySQL ngoài do Coolify quản lý (#38)"
- **Commit trong delta:** `239ad5e`, `3664ab4` (PR #36), `1ea1831`, `37c76e4`, `44fd69b`, `be3432a`, `a6076cb`, `84249d2`, `1fbd8bc` (merge #38), `2baf3a6`, `147fe54`
- **Code (runtime/config) được review tại:** `1fbd8bc` / `2baf3a6` — 5 file runtime giống hệt bản đã duyệt ở PR #38; commit `147fe54` (và mọi commit sau đó) chỉ là **docs-only bookkeeping**
- **Verdict ban đầu:** APPROVED WITH FOLLOW-UPS
- **Verdict cuối:** APPROVED WITH FOLLOW-UPS (re-review 1 tại `147fe54` — verdict **không đổi**)

## Tóm tắt

Đây là PR release `vc-dev` → `vc-prod` đưa nội dung PR #38 (production dùng MySQL ngoài do Coolify quản lý)
lên nhánh production. Review của PR #38 đã ở `vc-dev/pr-review/completed-pr-38-docker-production-external-db.md`
(verdict `APPROVED WITH FOLLOW-UPS`).

Phạm vi review lần này **chỉ là delta `vc-prod..vc-dev` và bản thân PR release**, không làm lại review #38:

- Delta có 11 file: 5 file runtime/config của PR #38 (đã review), 5 file tài liệu `vc-dev/**` của PR #38,
  và 1 file tài liệu từ PR #36 (`vc-dev/features/vc-theme-vcenter.md`).
- Đã xác minh bằng lệnh: **không có code mới nào lọt vào delta** — 5 file code/config giống hệt bản đã duyệt
  ở PR #38 (diff với commit merge `1fbd8bc` rỗng), các commit phía sau (`2baf3a6`, `147fe54`) đều là docs-only.
- Không có secret, không có file DB, không có đường dẫn cấm trong delta.
- Rủi ro còn lại của release: **điều kiện vận hành trên Coolify** phải được set trước khi deploy
  (xem Finding #1), và PR #36 chưa có review record (Finding #2 — đã có task theo dõi T14).

## Re-review 1 — 2026-10-22 (head `147fe54`)

Tác giả push thêm 1 commit sau SHA đã review (`2baf3a6`). Reviewer đã re-review delta mới và xác minh
từng claim bằng lệnh thật (không tin mô tả của PR body).

- **Head SHA re-review:** `147fe54af89c937e3c71703cebc1c883bd8ce019`
- **Delta commit(s) giữa 2 lần review:** `147fe54` — "docs(vc-dev): fix NIT review PR #39 + them follow-up T14"
- **Commit review (cập nhật):** `147fe54af89c937e3c71703cebc1c883bd8ce019`
- **Kết luận:** delta là **docs-only** (2 file, đều dưới `vc-dev/`), **không có file runtime nào đổi** ⇒
  nội dung deploy giống hệt bản đã duyệt ở PR #38 ⇒ verdict giữ nguyên `APPROVED WITH FOLLOW-UPS`.

### Bằng chứng (các lệnh đã chạy)

```
$ gh pr view 39 --json headRefOid --jq .headRefOid
147fe54af89c937e3c71703cebc1c883bd8ce019

$ git log --oneline 2baf3a6..147fe54
147fe54 docs(vc-dev): fix NIT review PR #39 + them follow-up T14

$ git diff --name-status 2baf3a6..147fe54
M       vc-dev/features/vc-theme-vcenter.md
M       vc-dev/issues/docker-production-external-db.md

$ git diff --stat 2baf3a6..147fe54
 vc-dev/features/vc-theme-vcenter.md            | 2 +-
 vc-dev/issues/docker-production-external-db.md | 5 +++--
 2 files changed, 4 insertions(+), 3 deletions(-)

$ git diff 2baf3a6..147fe54 -- docker-compose.production.yaml docker-compose.production.full.yaml docker-entrypoint.sh fresh-install.sh .dockerignore
(rỗng — không có file runtime nào thay đổi)

$ git diff --stat 1fbd8bc origin/vc-dev -- docker-compose.production.yaml docker-compose.production.full.yaml docker-entrypoint.sh fresh-install.sh .dockerignore
(rỗng — code vẫn đúng bản đã duyệt ở PR #38, commit merge 1fbd8bc)

$ git diff --stat origin/vc-prod...origin/vc-dev
 .dockerignore                                      |   2 +
 docker-compose.production.full.yaml                |  86 +++++
 docker-compose.production.yaml                     |  53 ++--
 docker-entrypoint.sh                               |  12 +-
 fresh-install.sh                                   |  91 ++++++
 vc-dev/features/vc-theme-vcenter.md                |  53 +++-
 vc-dev/instructions/skills.md                      |   2 +-
 vc-dev/issues/README.md                            |   1 +
 vc-dev/issues/docker-production-external-db.md     | 144 +++++++++
 vc-dev/pr-review/README.md                         |   1 +
 ...ompleted-pr-38-docker-production-external-db.md | 349 +++++++++++++++++++++
 11 files changed, 743 insertions(+), 51 deletions(-)
```

Claim (a) — chỉ thêm newline cuối `vc-dev/features/vc-theme-vcenter.md`, không đổi nội dung:

```
$ git diff 2baf3a6..147fe54 -- vc-dev/features/vc-theme-vcenter.md
@@ -259,4 +259,4 @@
-(chưa có — điền khi kết thúc phase)
\ No newline at end of file
+(chưa có — điền khi kết thúc phase)

# đối chiếu byte-level giữa 2 blob
$ git cat-file blob 2baf3a6:vc-dev/features/vc-theme-vcenter.md > old.md   # 19725 byte, byte cuối = 41 (')')
$ git cat-file blob 147fe54:vc-dev/features/vc-theme-vcenter.md > new.md   # 19726 byte, byte cuối = 10 (LF)
# old là tiền tố chính xác của new, new thêm đúng 1 byte LF ở cuối ⇒ chỉ khác newline cuối file
```

Claim (b) — plan có T14 và cập nhật phase:

```
$ Select-String -Path vc-dev/issues/docker-production-external-db.md -Pattern "T14|T7 —|Phase:"
75 : - [ ] PENDING: T7 — Mở PR vào `vc-prod` và merge (PR #39 đã mở, đã review `APPROVED WITH FOLLOW-UPS`)
85 : | T14 | review PR #39 — F2 | PR #36 (`feature/vc-theme-vcenter-plan` → `vc-dev`) đã merge nhưng **không có review record** (vi phạm `development.md` §2.3/§6.3). Nội dung chỉ là plan docs-only nên không ảnh hưởng production; cần bổ sung retro review record `vc-dev/pr-review/pr-36-vc-theme-vcenter-plan.md`. |
105: - Phase: PR #39 (`vc-dev` → `vc-prod`) đã mở, review `APPROVED WITH FOLLOW-UPS` — Đã xong: T1, T2, T3, T4, T5, T6, T12, T13 · Còn lại: T7 (merge PR #39), T8 + follow-up T9, T10, T11, T14
```

### Kết quả theo từng finding sau re-review 1

| # | Finding | Trạng thái | Bằng chứng / theo dõi |
|---|---|---|---|
| 1 | MINOR — điều kiện Coolify trước khi deploy (DB_HOST/DB_PASSWORD rỗng) | **STILL OPEN** | Task **T9** — `vc-dev/issues/docker-production-external-db.md:82`. **Chặn DEPLOY, không chặn merge.** |
| 2 | MINOR — PR #36 merge mà không có review record | **STILL OPEN** | Đã có task theo dõi **T14** — `vc-dev/issues/docker-production-external-db.md:85` (retro record `vc-dev/pr-review/pr-36-vc-theme-vcenter-plan.md`). Nội dung docs-only, không ảnh hưởng runtime ⇒ không chặn merge. |
| 3 | NIT — thiếu newline cuối `vc-dev/features/vc-theme-vcenter.md` | **FIXED** | Commit `147fe54` — file nay kết thúc bằng LF (byte cuối = 10), đối chiếu byte-level với blob cũ chỉ khác đúng 1 byte LF. |
| 4 | NIT — bookkeeping sau merge (T7, index, đổi tên file review) | **OPEN** | Ghi chú cho tác giả sau khi merge — không chặn merge. |

## Findings

### 1. MINOR — Điều kiện bắt buộc trên Coolify trước khi deploy: app sẽ không có DB nếu thiếu biến

- **Vị trí:** `docker-compose.production.yaml:32-36` (`DB_HOST: ${DB_HOST}`, `DB_PASSWORD: ${DB_PASSWORD}`),
  `docker-entrypoint.sh:24` (`if [ -n "$DB_HOST" ]`), `index.php:70-78` (redirect `/install/`)
- **Vấn đề:** sau khi merge, `docker-compose.production.yaml` trên production **không còn service `db`** và
  `DB_HOST`/`DB_PASSWORD` lấy từ Coolify Variables. Nếu deploy mà biến chưa được set:
  compose chỉ in warning rồi điền chuỗi rỗng ⇒ `DB_HOST=""` ⇒ entrypoint **bỏ qua hẳn vòng chờ DB**
  (`[ -n "$DB_HOST" ]` false, không in cảnh báo) ⇒ container vẫn boot, healthcheck `curl -f http://localhost:80/`
  trả 200 (trang installer) nên Coolify báo deploy thành công, nhưng site không có database.
  Đây chính là F1 của PR #38 (task **T9** vẫn PENDING).
  Lưu ý: merge vào `vc-prod` **không** tự deploy (repo không có `.github/workflows`, chỉ có `FUNDING.yml`;
  Coolify deploy là thao tác thủ công), nhưng nếu resource Coolify đang bật auto-deploy on push thì phải
  set biến **trước khi merge**.
- **Bằng chứng:**
  ```
  $ docker compose -f docker-compose.production.yaml config        # không set biến DB
  level=warning msg="The \"DB_HOST\" variable is not set. Defaulting to a blank string."
  level=warning msg="The \"DB_PASSWORD\" variable is not set. Defaulting to a blank string."
        DB_HOST: ""
        DB_PASSWORD: ""
  $ docker compose -f docker-compose.production.yaml config        # có set biến
        DB_HOST: coolify-db | DB_PORT: "3307" | DB_DATABASE: vvveb_prod
        DB_USER: coolify_user | DB_PASSWORD: s3cret | DB_ENGINE: mysqli
  $ git show origin/vc-prod:docker-compose.production.yaml | Select-String "DB_HOST"
        DB_HOST: db                    # compose đang chạy ở production hiện tại
  ```
- **Fix:** trước khi deploy (và trước khi merge nếu bật auto-deploy):
  (1) Coolify Variables: `DB_HOST` (bắt buộc), `DB_PASSWORD` (bắt buộc), `DB_PORT` (mặc định 3306),
  `DB_DATABASE` (mặc định `vvveb`), `DB_USER` (fallback `DB_USERNAME` do Coolify inject), `APP_KEY`, `CRON_KEY`;
  (2) dùng "Connect to Predefined Database" của Coolify để DB chung network với app, hoặc khai báo
  `networks: external: true` trỏ network Coolify; (3) kiểm tra `docker compose -f docker-compose.production.yaml config`
  **không còn warning "variable is not set"**; (4) sau deploy, mở `/` và kiểm tra app nối được DB.
- **Trạng thái (re-review 1 @ `147fe54`):** **STILL OPEN** — theo dõi bằng task **T9** trong
  `vc-dev/issues/docker-production-external-db.md:82` (bảng `### Follow-up (từ review PR #38 — MINOR/NIT chưa fix trong PR này)`).
  Delta `2baf3a6..147fe54` không đụng vào `docker-compose.production.yaml` ⇒ phát hiện vẫn còn hiệu lực.
  **Chặn DEPLOY, không chặn merge.**

### 2. MINOR — File của PR #36 vào production mà PR #36 chưa có review record (vi phạm §2.3/§6.3)

- **Vị trí:** `vc-dev/features/vc-theme-vcenter.md` (đưa vào bởi PR #36: commit `239ad5e`, merge `3664ab4`)
- **Vấn đề:** PR #36 (`feature/vc-theme-vcenter-plan` → `vc-dev`) đã merge mà **không có file review record**
  trong `vc-dev/pr-review/`, trái `development.md` §2.3 ("Không có file này ⇒ không merge") và
  `agent-guide.md` §1.8. Delta của PR release này mang file đó lên `vc-prod`.
  Về nội dung: file là **plan docs-only** — không có code runtime, không có secret, tasklist T1–T6 đều
  `PENDING`, `## Handoff Summary` còn là "(chưa có — điền khi kết thết phase)" ⇒ **không ảnh hưởng runtime
  production**, nên không chặn release này; nhưng đây là nợ quy trình cần được ghi nhận.
- **Bằng chứng:**
  ```
  $ gh pr view 36 --json state,mergedAt,files
  {"state":"MERGED","mergedAt":"2026-10-22T09:17:35Z","files":["vc-dev/features/vc-theme-vcenter.md"]}
  $ git show --stat 3664ab4
   vc-dev/features/vc-theme-vcenter.md | 55 ++++++++++++++++++++++++++++---------   # chỉ 1 file markdown
  $ Get-ChildItem vc-dev\pr-review | Select-Object -ExpandProperty Name
  # không có file nào chứa "36"
  $ git diff origin/vc-prod...origin/vc-dev -- vc-dev/features/vc-theme-vcenter.md
  # chỉ thay đổi markdown: thêm mục "Subagent Parallel Assignment", đổi tasklist T1–T7 → T1–T6
  ```
- **Fix:** tạo retro review record cho PR #36 (`vc-dev/pr-review/pr-36-vc-theme-vcenter-plan.md`, nội dung
  docs-only nên review nhanh) hoặc ghi rõ nợ quy trình vào plan hiện tại + cập nhật index
  `vc-dev/pr-review/README.md`.
- **Trạng thái (re-review 1 @ `147fe54`):** **STILL OPEN**, nhưng **đã có task theo dõi**:
  **T14** — `vc-dev/issues/docker-production-external-db.md:85`
  (`| T14 | review PR #39 — F2 | PR #36 … cần bổ sung retro review record vc-dev/pr-review/pr-36-vc-theme-vcenter-plan.md |`).
  Delta `2baf3a6..147fe54` chỉ sửa file plan của PR #36 (thêm newline cuối — xem Finding #3), không tạo
  review record cho PR #36 ⇒ nợ quy trình vẫn còn, nhưng không chặn merge (nội dung docs-only, không có code/secret).

### 3. NIT — File plan của PR #36 thiếu newline ở cuối file — **FIXED**

- **Vị trí:** `vc-dev/features/vc-theme-vcenter.md` (dòng cuối: `(chưa có — điền khi kết thúc phase)`)
- **Vấn đề (lúc review đầu):** diff cho thấy `\ No newline at end of file` ⇒ mỗi lần sửa file này sinh diff nhiễu ở dòng cuối.
- **Fix:** thêm newline cuối file khi chỉnh plan lần tới.
- **Trạng thái (re-review 1 @ `147fe54`):** **FIXED** — commit `147fe54` đã thêm newline cuối file.
  Xác minh byte-level: blob `147fe54` dài 19726 byte, byte cuối = 10 (LF); blob `2baf3a6` dài 19725 byte,
  byte cuối = 41 (`)`) và là **tiền tố chính xác** của blob mới ⇒ nội dung không đổi, chỉ thêm 1 byte LF.

### 4. NIT — Sau khi merge PR release, plan và index vẫn ghi trạng thái cũ (ghi chú cho tác giả)

- **Vị trí:** `vc-dev/issues/docker-production-external-db.md:6` (`Trạng thái tổng: IN_PROGRESS`),
  `vc-dev/issues/docker-production-external-db.md:75-76` (T7, T8 `PENDING`),
  `vc-dev/issues/README.md:24` (dòng index trạng thái `IN_PROGRESS`)
- **Vấn đề:** sau khi PR #39 merge, T7 (mở PR vào `vc-prod`) và T8 (sync ngược) chưa thể đánh dấu hoàn tất
  ngay ở commit merge; index `vc-dev/pr-review/README.md` cũng cần đổi trạng thái PR #39 sau merge.
  Delta `2baf3a6..147fe54` đã cập nhật T7 và dòng Phase (ghi rõ PR #39 đã review `APPROVED WITH FOLLOW-UPS`),
  nhưng T7 vẫn phải đánh dấu `COMPLETED` sau khi merge thật.
- **Fix:** sau merge — đánh dấu T7 `COMPLETED`, đổi trạng thái dòng index PR #39 thành `MERGED`, đổi tên file
  review thành `completed-pr-39-release-vc-prod-docker-external-db.md`; sau khi PR sync ngược (T8) thì
  đổi trạng thái plan sang `MERGED`.
- **Trạng thái (re-review 1 @ `147fe54`):** **OPEN** — ghi chú cho tác giả, không chặn merge.

## Claim đã xác minh đúng

- Claim "PR này chỉ đưa nội dung PR #38 đã review lên production, không có code mới" — ĐÚNG:
  `git diff 1fbd8bc origin/vc-dev -- docker-compose.production.yaml docker-compose.production.full.yaml
  docker-entrypoint.sh fresh-install.sh .dockerignore` → **rỗng**; `git show --stat 2baf3a6` cho thấy commit
  duy nhất sau `1fbd8bc` chỉ đổi `vc-dev/issues/docker-production-external-db.md`,
  `vc-dev/pr-review/README.md` và rename `pr-38-…md` → `completed-pr-38-…md` (docs-only).
- Claim "PR #39 chỉ có 2 thay đổi docs (newline + T14), không đụng code" (claim của tác giả ở re-review 1) — ĐÚNG:
  `git diff --name-status 2baf3a6..147fe54` chỉ có `vc-dev/features/vc-theme-vcenter.md` và
  `vc-dev/issues/docker-production-external-db.md`; diff với 5 file runtime **rỗng**; byte-level xác nhận
  file plan chỉ thêm newline cuối và plan chỉ thêm T14 + cập nhật T7/Phase.
- Claim "`docker-compose.production.full.yaml` là phương án rollback của cấu hình production hiện tại" — ĐÚNG,
  **giống hệt từng byte**: `git rev-parse origin/vc-prod:docker-compose.production.yaml` =
  `git rev-parse origin/vc-dev:docker-compose.production.full.yaml` = `411c06845a4785762d5a7055aa2c99862fdb2b22`;
  `git diff origin/vc-prod:docker-compose.production.yaml origin/vc-dev:docker-compose.production.full.yaml` rỗng.
- Claim "File của PR #36 là docs-only, không có code/secret" — ĐÚNG: PR #36 chỉ có
  `vc-dev/features/vc-theme-vcenter.md` (markdown); diff delta chỉ thêm mục "Subagent Parallel Assignment"
  và đổi tasklist; grep delta không thấy token/password/API key.
- Claim "Không có secret / file DB / đường dẫn cấm trong delta" — ĐÚNG:
  `git diff --name-status origin/vc-prod...origin/vc-dev` → 11 file, **không có** `storage/**`, `vc-dev/wip/**`,
  `config/db.php`; toàn bộ credential trong compose chỉ là placeholder `${DB_HOST}`/`${DB_PASSWORD}`/…
  (`git ls-files storage/sqlite` vẫn còn `storage/sqlite/vvveb.db` từ commit cũ `b0d7aa5` — pre-existing,
  không nằm trong delta này và `.dockerignore` đã loại `storage/` khỏi build context).
- Claim "Review record của PR #38 tồn tại và verdict APPROVED WITH FOLLOW-UPS" — ĐÚNG:
  `vc-dev/pr-review/completed-pr-38-docker-production-external-db.md` tồn tại, header ghi
  `Verdict ban đầu: APPROVED WITH FOLLOW-UPS` · `Verdict cuối: APPROVED WITH FOLLOW-UPS`,
  commit review `a6076cba…`; index `vc-dev/pr-review/README.md:32` ghi trạng thái `MERGED`.
- Claim "T9/T10/T11 tồn tại và được nhắc trong PR body" — ĐÚNG: bảng
  `### Follow-up (từ review PR #38 — MINOR/NIT chưa fix trong PR này)` trong
  `vc-dev/issues/docker-production-external-db.md:82-84` có T9 (F1), T10 (F2), T11 (F3); PR body mục
  "Bắt buộc khi deploy (Coolify)" liệt kê đúng T9, T10, T11.
- Claim "T14 tồn tại và trỏ đúng nợ PR #36" (re-review 1) — ĐÚNG: `vc-dev/issues/docker-production-external-db.md:85`
  có dòng `| T14 | review PR #39 — F2 | PR #36 … cần bổ sung retro review record
  vc-dev/pr-review/pr-36-vc-theme-vcenter-plan.md |`.
- Claim "Plan đủ dùng cho release: T6 xong, T7 = PR này, T8 = sync ngược, có Handoff Summary" — ĐÚNG:
  `docker-production-external-db.md:68` T6 `COMPLETED`, `:75-76` T7/T8 `PENDING`, `## Handoff Summary` ở `:100`.
- Claim "Test Plan trung thực (Coolify E2E chưa verify)" — ĐÚNG: `docker-production-external-db.md:97` để nguyên
  `- [ ] PENDING: Manual — Coolify deploy thật…`; PR body cũng tick `[ ]` cho mục này. **Reviewer không đồng ý
  để trạng thái này được present như đã verify.**
- Claim "Các test đã tick trong PR body vẫn đúng ở head hiện tại" — ĐÚNG (chạy lại tại `2baf3a6`):
  `docker compose -f docker-compose.production.yaml config` render đúng `DB_HOST/DB_PORT/DB_DATABASE/DB_USER/DB_PASSWORD`;
  `bash -n docker-entrypoint.sh` → exit 0; `bash -n fresh-install.sh` → exit 0.
  Delta `2baf3a6..147fe54` không đụng các file này ⇒ kết quả này vẫn hiệu lực tại `147fe54`.
- Claim "Không có CI auto-deploy trong repo" — ĐÚNG: `Test-Path .github\workflows` → False; `git ls-files ".github/*"`
  chỉ có `.github/FUNDING.yml`. Việc deploy production là thao tác thủ công trên Coolify.

## Claim không xác minh được

- Claim "Coolify deploy thật sẽ chạy được: app nối được MySQL ngoài, `/install/` chạy được, healthcheck healthy"
  — CHƯA XÁC MINH ĐƯỢC: máy reviewer không có Docker daemon và không truy cập được instance Coolify
  ⇒ không dựng được stack end-to-end. Test này vẫn đang PENDING trong plan và PR body (đúng như thực tế).
- Claim "Coolify inject `DB_USERNAME` cho Predefined Database MySQL" — CHƯA XÁC MINH ĐƯỢC trong repo
  (hỗ trợ từ tài liệu/mẫu Coolify: `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).
- Claim "App và container DB cùng Docker network" — CHƯA XÁC MINH ĐƯỢC (thuộc ops, cần Coolify thật).
- Claim "Resource Coolify này có bật auto-deploy on push hay không" — CHƯA XÁC MINH ĐƯỢC (cần truy cập UI Coolify);
  đây là điều kiện quyết định mức khẩn cấp của Finding #1.
- Claim "Production hiện đã có dữ liệu thật trong volume `vvveb_db_data` hay chưa" — CHƯA XÁC MINH ĐƯỢC
  (cần truy cập host); quyết định có cần migrate dữ liệu (T11) phụ thuộc câu trả lời này.
- Claim "Volume `vvveb_db_data` còn tồn tại sau khi deploy compose mới" — CHƯA XÁC MINH ĐƯỢC (cần host thật).
- Claim "Retro review record cho PR #36 đã được tạo" — CHƯA XÁC MINH ĐƯỢC/không có: tại `147fe54` thư mục
  `vc-dev/pr-review/` vẫn chưa có file nào cho PR #36 (task T14 vẫn PENDING).

## Quyết định

**APPROVED WITH FOLLOW-UPS.** Không có `BLOCKER`/`MAJOR` OPEN trong delta. Nội dung deploy của PR này
**đúng bằng** phần đã review và duyệt ở PR #38 (không có code lọt vào ngoài); các file kèm theo là tài liệu
`vc-dev/**`; không có secret, file DB hay đường dẫn cấm. Re-review 1 tại `147fe54` xác nhận delta mới
**docs-only** và Finding #3 đã được fix.

**Điều kiện bắt buộc trước khi deploy production (theo Finding #1 — task T9):**

1. Coolify Variables: `DB_HOST` (bắt buộc), `DB_PASSWORD` (bắt buộc), `DB_PORT` (mặc định 3306),
   `DB_DATABASE` (mặc định `vvveb`), `DB_USER` (fallback `DB_USERNAME` do Coolify inject), `APP_KEY`, `CRON_KEY`.
2. App và container DB phải cùng Docker network — dùng "Connect to Predefined Database" của Coolify
   (hoặc khai báo network `external: true` trỏ network Coolify).
3. Kiểm tra `docker compose -f docker-compose.production.yaml config` **không còn warning**
   "The \"DB_HOST\" variable is not set" / "The \"DB_PASSWORD\" variable is not set" trước khi deploy.
4. Sau deploy: mở `/` và xác nhận app nối được DB (không phải trang installer), healthcheck healthy.
5. Nếu production đã có dữ liệu thật: dump từ volume `vvveb_db_data` rồi restore vào DB ngoài (task T11).

**Ghi chú quy trình (không chặn merge):** PR #36 đã vào `vc-dev` mà không có review record — task **T14**
đã được tạo trong `vc-dev/issues/docker-production-external-db.md:85` (nội dung retro record vẫn OPEN).
Sau khi merge PR #39: đánh dấu T7 `COMPLETED`, đổi trạng thái dòng index PR #39 thành `MERGED`,
đổi tên file review thành `completed-pr-39-release-vc-prod-docker-external-db.md` (Finding #4).

### Điều kiện merge (cập nhật sau re-review 1)

- **Code (5 file runtime/config) được review tại `1fbd8bc` / `2baf3a6`** và **không đổi** tới `147fe54`
  (`git diff 1fbd8bc origin/vc-dev -- <5 file runtime>` rỗng).
- **Commit review hiện tại:** `147fe54af89c937e3c71703cebc1c883bd8ce019` (head PR #39 tại thời điểm re-review).
- Có thể merge khi head của PR **là `147fe54`**; nếu tác giả push thêm commit thì chỉ được là commit
  **docs-only bookkeeping** (chỉ đụng `vc-dev/pr-review/**` và `vc-dev/issues/**` — ví dụ commit chính file
  review record này + cập nhật index), tương tự tiền lệ PR #13 (review record được commit docs-only ở
  `7df1131`, merge ở `418753d` — commit mang review record).
- Nếu có commit mới đụng **file runtime** ⇒ bắt buộc re-review delta mới (theo `development.md` §6.3).

## Checklist hoàn tất review (trước khi merge)

- [x] File `vc-dev/pr-review/pr-39-release-vc-prod-docker-external-db.md` tồn tại.
- [x] `Commit review` khớp `gh pr view 39 --json headRefOid` = `147fe54af89c937e3c71703cebc1c883bd8ce019` (re-review 1).
- [x] Delta re-review `2baf3a6..147fe54` chỉ gồm 2 file dưới `vc-dev/` (`git diff --name-status`); không file runtime nào đổi.
- [x] Nội dung deploy vẫn đúng bản đã duyệt ở PR #38 (diff với `1fbd8bc` rỗng cho 5 file runtime).
- [x] Delta release so với `vc-prod` vẫn chỉ gồm nội dung đã review ở PR #38 + tài liệu (11 file, `git diff --stat origin/vc-prod...origin/vc-dev`).
- [x] Finding #3 (newline cuối file) — **FIXED** tại `147fe54` (xác minh byte-level).
- [x] Finding #2 (PR #36 chưa có review record) — **STILL OPEN** nhưng đã có task theo dõi **T14**
      (`vc-dev/issues/docker-production-external-db.md:85`); nội dung retro record vẫn cần làm.
- [x] Finding #1 (điều kiện Coolify) — **STILL OPEN**, theo dõi bằng task **T9**; **chặn DEPLOY, không chặn merge**.
- [x] Verdict cuối `APPROVED WITH FOLLOW-UPS`; không còn `BLOCKER`/`MAJOR` OPEN.
- [x] Review record PR #38 (`completed-pr-38-…md`, verdict `APPROVED WITH FOLLOW-UPS`) tồn tại và được link.
- [x] Index `vc-dev/pr-review/README.md` đã cập nhật (dòng PR #39 — verdict cuối không đổi nên trạng thái dòng giữ nguyên `READY TO MERGE`).
- [ ] Trước khi deploy production: đã set Coolify Variables + network theo Finding #1 (chưa verify được từ repo).
- [ ] Sau merge: đánh dấu T7 `COMPLETED`, đổi index PR #39 → `MERGED`, đổi tên file review → `completed-pr-39-…md` (Finding #4).
- [ ] Sau merge: tạo retro review record PR #36 (`vc-dev/pr-review/pr-36-vc-theme-vcenter-plan.md`) theo T14.
