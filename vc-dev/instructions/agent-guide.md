# Agent Guide — Quy ước & ràng buộc cho AI Agents

> **Đọc file này trước khi thực hiện bất kỳ thay đổi nào.**
> Bổ trợ cho `architecture.md` (bối cảnh) và `development.md` (quy trình).
> Ngắn gọn, ưu tiên danh sách quyết định — đây là contract, không phải tài liệu tham khảo.

---

## 1. Nguyên tắc gốc (không được vi phạm)

1. **Minimal Core Code Modification.** Luôn chọn mức can thiệp thấp nhất trong `architecture.md` §4.2 (plugin → theme → config → event hook → create-only → patch core).
2. **Plan trước, code sau.** Không viết một dòng code nào trước khi tồn tại file Plan ở `vc-dev/features/<name>.md` hoặc `vc-dev/issues/<name>.md` (xem `development.md` §4).
3. **Baseline bất khả xâm phạm.** Không commit vào `master`. Không rebase/force-push `master` hay `vc-dev`. Không merge từ nhánh lạ.
4. **Tôn trọng work của người khác.** File lạ, thay đổi chưa commit, junction/symlink, thư mục vendor bị sửa ⇒ điều tra trước, không xóa/ghi đè.
5. **Không bịa.** Không suy đoán hành vi API/DB/schema. Đọc code thật hoặc grep. Nếu không chắc ⇒ ghi `BLOCKED` + câu hỏi cụ thể vào plan.
6. **Hoàn tất phải để lại dấu vết.** Mỗi phase kết thúc ⇒ `## Handoff Summary` (định dạng ở `development.md` §4.5).

---

## 2. File/folder CẤM sửa trực tiếp (hard deny)

| Đường dẫn | Lý do |
|---|---|
| `vendor/**` | Dependency, sinh bởi composer |
| `storage/**` | Runtime data (cache, compiled templates, logs, backup) — sinh tự động |
| `public/page-cache/**`, `public/assets-cache/**`, `public/image-cache/**` | Cache runtime |
| `public/media/**` | Media người dùng upload |
| `config/db.php` | Credential DB, sinh bởi installer — **không commit** |
| `config/app.php` | Chứa `key`/`cronkey` — chỉ sửa qua installer/admin UI |
| `node_modules/**` | Dependency JS |
| `public/js/libs/**` | Thư viện bên thứ ba (tint, swiper, coloris, glightbox…) |
| `public/resources/svg/icons/**` | 24 bộ icon vendor |
| `public/admin/default/**`, `public/themes/landing/**`, `public/themes/blog-default/**`, `plugins/{contact-form,payment,shipping}/**`, `public/js/libs/tinymce-dist/**` | **Thư mục vendor** (Phương án D — không còn submodule) — chỉ cập nhật qua quy trình vendor refresh `architecture.md` §2.4, không sửa trực tiếp từng file |
| `public/*.html` sinh tự động, `public/themes/*/[a-z]*.html` build output | Nguồn thật là `src/` + gulp ở **repo phụ** (`architecture.md` §2.4) |
| `*.zip`, `vvveb.zip` | Artifact build |
| `.git/**` (trực tiếp) | Chỉ dùng lệnh git |

**Ngoại lệ duy nhất:** khi task là **vendor refresh** (`architecture.md` §2.4) — thao tác bằng `robocopy /MIR` từ repo phụ, ghi lại commit nguồn vào bảng §2.5, **không** chỉnh tay từng file trong core.

---

## 3. Nơi ĐƯỢC PHÉP thêm / ghi đè

| Nhu cầu | Vị trí | Ghi chú |
|---|---|---|
| Tính năng mới có vòng đời riêng | `plugins/<new-slug>/**` | Ưu tiên #1 |
| Giao diện frontend mới | `public/themes/<new-slug>/**` | Chỉ tạo **theme mới**, không sửa theme có sẵn |
| Route/menu/custom type | Đăng ký qua `Event::on()` trong `plugin.php` | Không sửa `config/*-routes.php` của core |
| Config riêng của tính năng | `Setting::setSetting()` (DB) hoặc file `config/<ns>.php` **mới** | Không nhồi key vào `config/app.php` |
| Tài liệu, plan, tasklist | `vc-dev/**` | Tự do |
| Script tiện ích dự án | `vc-dev/scripts/**` (tạo khi cần) | Không đặt trong `system/` |

---

## 4. Khi buộc phải sửa file core (mức 6)

Điều kiện: không plugin/theme/config/event nào đáp ứng được **và** đã ghi vào `## Scope & Impact Analysis`.

Bắt buộc 3 việc:
1. **Marker comment** quanh vùng sửa:
   ```php
   // >>> vc-dev:patch <slug> — <lý do ngắn> (upstream: <link nếu có>)
   ... thay đổi ...
   // <<< vc-dev:patch
   ```
2. **Ghi vào plan**: file, dòng, lý do, kế hoạch rollback.
3. **Khai báo ảnh hưởng upstream sync**: có thể tự động merge không, cần can thiệp thủ công ở đâu.

Kiểm tra marker hiện có trước khi rebase: `grep -rn "vc-dev:patch" --include=*.php .`

---

## 5. Coding standards

### 5.1 PHP

- **Style:** theo file xung quanh — core dùng **tab** để indent, `namespace Vvveb\...`, `use` ở đầu file.
- **PHP tương thích:** viết code chạy được trên **PHP 7.4** (không dùng `enum`, `readonly`, `never`, named args, `match` nếu chưa cần). Property promotion/union type chỉ khi thực sự cần → cân nhắc.
- **Namespace:** controller `Vvveb\Controller\<Module>` (app), `Vvveb\Admin\Controller\<Module>`, plugin `Vvveb\Plugins\<Name>\...`.
- **Class kết thúc bằng `SQL`** ⇒ core compile từ `sql/<engine>/*.sql` thành model. Đừng đổi tên class nếu không đổi tên file `.sql` tương ứng.
- **Bảo mật:**
  - Escape output HTML: dùng helper của hệ thống, không `echo` dữ liệu user thô.
  - SQL: dùng tham số của lớp model/DB; **không** nối chuỗi SQL.
  - Không `eval`, không `extract()` trên dữ liệu request, không `include` đường dẫn từ input.
  - Kiểm tra quyền admin trước mọi action quản trị (`base.php` của admin cung cấp sẵn — dùng nó, đừng tự chế).
- **Event callback:**
  ```php
  Event::on($ns, $name, $id, function (&$arg1, $arg2) {
      // ... thay đổi ...
      return [$arg1, $arg2];      // ⚠️ LUÔN return mảng tham số
  }, $priority);
  ```
  `$id` phải **duy nhất** — dùng `__FILE__`, `__CLASS__ . '::' . __FUNCTION__`, hoặc `'<slug>:<purpose>'`. Trả về `null` sẽ khiến core giữ giá trị cũ (chỉ dùng khi cố ý).
- **Không thêm `console.log`/`var_dump`/`print_r`** vào commit. Nếu cần debug, dùng `error_log()` có prefix slug.
- **Không** thêm `die()`/`exit()` trong code chạy trong request flow (trừ khi có lý do rõ, ghi trong plan).
- Luôn `php -l` file sau khi sửa.

### 5.2 JavaScript (VvvebJs builder & admin JS)

- `public/js/vvvebjs/**` là **vendor + patch** — chỉ sửa khi task yêu cầu rõ, ghi marker:
  ```js
  // >>> vc-dev:patch <slug> — <lý do>
  ```
- Vanilla JS, không thêm framework. Không đổi API public của `builder.js` (`Vvveb.Builder`).
- Không sửa file trong `public/js/libs/**`.

### 5.3 Markdown (tài liệu, plan)

- Heading dạng câu ngắn, không dấu chấm cuối.
- Tasklist đúng cú pháp `- [ ] PENDING: …` / `- [x] COMPLETED: …`.
- Không dùng HTML thô, không emoji trong tài liệu kỹ thuật.

---

## 6. Naming conventions

| Đối tượng | Quy ước | Ví dụ |
|---|---|---|
| Plugin folder | kebab-case, chữ thường | `loyalty-points` |
| Plugin namespace | PascalCase, không gạch | `Vvveb\Plugins\LoyaltyPoints` |
| Theme folder | kebab-case | `corp-landing` |
| Feature/issue plan file | kebab-case | `vc-dev/features/loyalty-points.md` |
| Branch | `feature/`, `fix/`, `chore/`, `sync/`, `docs/` | `fix/12-checkout-currency` |
| Event id | `<slug>:<purpose>` hoặc `__FILE__` | `'loyalty-points:add-points'` |
| Patch marker slug | trùng slug feature/issue | `vc-dev:patch loyalty-points` |
| Task id trong plan | `T1`, `T2`, … (không đổi số sau khi tạo) | `T3` |
| Config namespace | kebab-case, trùng slug plugin | `loyalty-points` |
| SQL table | snake_case, có prefix site nếu cần | `loyalty_points` |

---

## 7. Do / Don't

**DO**
- ✅ Đọc `architecture.md §3` để tìm extension point **trước khi** nghĩ tới sửa core.
- ✅ Grep để xác minh: `grep -rn "Event::on" --include=*.php .`, `grep -rn "function <name>" system/`.
- ✅ Kiểm tra thư mục vendor có nội dung không (`(Get-ChildItem public\themes\landing | Measure-Object).Count`) và `config/db.php` tồn tại trước khi kết luận "không render được".
- ✅ Cập nhật tasklist ngay khi trạng thái thay đổi.
- ✅ Ghi lại cạm bẫy đã gặp trong `Handoff Summary → Context for Next Agent`.
- ✅ Nêu rõ giới hạn: ví dụ "chưa test được admin UI vì thư mục vendor `public/admin/default` còn rỗng (migration §2.6 chưa chạy)".
- ✅ Ghi lại **commit nguồn** vào bảng `architecture.md` §2.5 mỗi khi vendor refresh.

**DON'T**
- ❌ Code trước khi có Plan file.
- ❌ Chỉnh tay file trong thư mục vendor (`public/themes/landing`, `public/admin/default`, `public/themes/blog-default`, `plugins/{contact-form,payment,shipping}`, `public/js/libs/tinymce-dist`) — phải sửa ở repo phụ rồi vendor refresh (`architecture.md` §2.4).
- ❌ Copy đè thư mục vendor bằng `xcopy`/`Copy-Item` mà không dùng mirror có kiểm soát (`robocopy /MIR`) — sẽ để lại file cũ không còn tồn tại ở nguồn.
- ❌ Mang `.git/` hoặc `node_modules/` của repo phụ vào core khi vendor hóa.
- ❌ Sửa file core để "tiện"; sửa để workaround lỗi cấu hình (thường nguyên nhân là thư mục vendor rỗng hoặc thiếu `config/db.php`).
- ❌ Chạy `npm install`/`gulp` **trong core** (`public/admin/default`, `public/themes/*`) rồi commit artifact build — build phải chạy ở repo phụ.
- ❌ Chạy `build.sh` như bước "test" (nó xóa cache + tạo zip).
- ❌ `git add -A` mù quáng — luôn xem `git status` trước; nhiều thư mục runtime bị ignore nhưng có thể xuất hiện file lạ.
- ❌ Commit `config/db.php`, secret, log, `*.zip`, `node_modules`.
- ❌ Tự ý đổi `env.php` (đặc biệt `DEBUG`/`PAGE_CACHE`) mà không ghi trong plan.
- ❌ Xóa hoặc ghi đè task/handoff của agent/developer khác.
- ❌ Báo "hoàn thành" khi chưa chạy test ở `development.md` §5 hoặc chưa có Handoff Summary.

---

## 8. Quy trình làm việc của Agent (tóm tắt 7 bước)

```
1. ĐỌC     : vc-dev/instructions/architecture.md §2 + §3, agent-guide.md (file này)
2. ĐỊNH VỊ  : git status, git branch --show-current, kiểm tra thư mục vendor, ls config/db.php
3. PLAN    : tạo/cập nhật vc-dev/{features|issues}/<name>.md — KHÔNG code trước bước này
4. BRANCH  : git checkout vc-dev && git pull && git checkout -b <type>/<slug>
5. LÀM     : theo tasklist; cập nhật trạng thái realtime; mức can thiệp core thấp nhất
             (nếu chạm UI vendor ⇒ theo quy trình vendor refresh architecture.md §2.4)
6. TEST    : development.md §5 — php -l, clear cache, smoke test, ghi bằng chứng
7. BÀN GIAO: điền ## Handoff Summary + commit + (nếu xong) mở PR vào vc-dev
```

---

## 9. Mẫu khối Handoff tối thiểu (dùng khi context hạn chế)

```markdown
## Handoff Summary
### Status Current Phase
- Phase: <tên> · Đã xong: T1,T2 · Còn lại: T3 BLOCKED (<lý do>)
- Commit cuối: <hash> trên `<branch>` · Test: <lệnh + kết quả>
### Key Decisions & Findings
1. <quyết định> — <lý do>
2. <phát hiện> — <hệ quả cho agent sau>
### Context for Next Agent
- Đã sửa/tạo: <file>
- Lệnh cần chạy: <lệnh>
- Cạm bẫy: <mô tả>
### Next Action Items
1. <việc cụ thể, nêu file>
```

---

## 10. Khi bị chặn (BLOCKED)

Đặt `**Trạng thái tổng:** BLOCKED`, ghi task `BLOCKED: <task> — <lý do>`, và trong Handoff nêu **chính xác**:
- Cần gì: quyền ghi file? DB? quyết định thiết kế? thông tin còn thiếu?
- Từ ai: developer/human/agent nào.
- Bằng chứng: lệnh đã chạy + output lỗi (đã lược bỏ secret).

Không tự ý chọn một hướng "tạm ổn" rồi tiếp tục khi bị chặn ở quyết định kiến trúc.
