# PR #20 — `feature/i18n-vietnamese`

- **Reviewer:** independent session (Kilo agent, context sạch, khác tác giả PR)
- **Ngày:** 2026-10-22
- **Base:** `vc-dev` · **Commit review:** `ad9c81ecf9a42916337cb4a028c868dbb51c2094`
- **Verdict ban đầu:** CHANGES REQUIRED
- **Verdict cuối:** CHANGES REQUIRED

## Findings

### 1. MAJOR — `locale/vi_VN/LC_MESSAGES/vvveb.mo` là file rỗng/không hợp lệ (28 bytes, không chứa translation)
- **Vị trí:** `locale/vi_VN/LC_MESSAGES/vvveb.mo` (ADDED, 28 bytes)
- **Vấn đề:** File `.mo` chỉ có header magic (`0xDE120495`) rồi toàn byte 0, không chứa mục translation nào. Có 57 msgid trong `.po` nhưng `.mo` compile ra không có translation ⇒ gettext sẽ không dịch được gì cả. PR description claim "compiled via phpmo_write_mo_file" nhưng file `.mo` thực tế không chứa dữ liệu dịch.
- **Bằng chứng:** `git show 20:locale/vi_VN/LC_MESSAGES/vvveb.mo` kích thước 28 bytes; hexdump header `de 12 04 95` rồi toàn `00`; table offsets/count đều 0. So sánh một `.mo` hợp lệ của các ngôn ngữ khác (nếu có) sẽ có kích thướcKB.
- **Fix:** Biên dịch lại `.po` thành `.mo` đúng chuẩn GNU MO (msgfmt / công cụ compile đầy đủ) sao cho bảng translation chứa tất cả 57 msgid. Hoặc xóa file `.mo` invalid và để admin localization compile lại.
- **Trạng thái:** OPEN

### 2. MINOR — Plan file `vc-dev/features/i18n-vietnamese.md` đánh giá tự cao, Test Plan còn PENDING
- **Vị trí:** `vc-dev/features/i18n-vietnamese.md:36-39` (Test Plan) và `:43-48` (Handoff Summary)
- **Vấn đề:** Handoff Summary ghi "Test đã chạy: Compile vvveb.mo bằng phpmo_write_mo_file qua logic admin (OK)" nhưng Test Plan mục "Manual — xác minh không sửa file cấm, translations sẵn sàng áp dụng" vẫn ở `PENDING`; claim compile OK bị mâu thuẫn với finding #1 (file `.mo` invalid). Vì không có test framework nên cần bằng chứng thủ công rõ ràng.
- **Fix:** Cập nhật Test Plan thành COMPLETED kèm lệnh xác minh thực tế (ví dụ `php -l` không áp dụng được `.po`/`.mo`, kiểm tra kích thước `.mo` hợp lệ, chạy `Locale::installedLanguages()` trong bootstrap đầy đủ) hoặc ghi đúng trạng thái chưa verify được. Sửa Handoff Summary cho khớp thực tế sau khi fix finding #1.
- **Trạng thái:** OPEN

### 3. NIT — `.po` file bắt đầu không có BOM (UTF-8 không BOM là hợp lệ, nhưng cần lưu ý)
- **Vị trí:** `locale/vi_VN/LC_MESSAGES/vvveb.po:1`
- **Vấn đề:** Byte đầu là `239 187 191` (`EF BB BF` = UTF-8 BOM) — gettext xử lý được BOM nhưng một số toolchain cũ có thể bị ảnh hưởng. Không chặn merge, chỉ ghi nhận.
- **Trạng thái:** REJECTED — chấp nhận, UTF-8 BOM không lỗi với gettext.

## Claim đã xác minh đúng
- Claim "Files `.po/.mo` lưu tại `locale/{lang}/LC_MESSAGES/`" — ĐÚNG: `system/locale.php:147` dùng `bindtextdomain($domain, DIR_ROOT . 'locale')`; `system/functions.php:529` dùng `DIR_ROOT . 'locale' . DS . $lang . DS 'LC_MESSAGES' . DS`.
- Claim "`system/data/languages-list.php` đã có entries `vi` và `vi_VN`" — ĐÚNG: dòng 2475 (`vi_VN`) và 2481 (`vi`).
- Claim không sửa file cấm (vendor, core PHP/JS) — ĐÚNG: diff chỉ thêm `locale/vi_VN/*`, `vc-dev/features/i18n-vietnamese.md`, sửa `vc-dev/features/README.md`; không có file vendor/core trong diff.
- Claim `.po` có 201 dòng / 57 msgid — ĐÚNG (đếm trực tiếp file).

## Claim không xác minh được
- Claim "Compile vvveb.mo bằng phpmo_write_mo_file qua logic admin (OK)" — KHÔNG XÁC MINH ĐƯỢC: không có lệnh admin được chạy trong PR, không có output/log compile, và file `.mo` kết quả invalid (finding #1).
- Claim test đã chạy + OK — KHÔNG XÁC MINH ĐƯỢC: Test Plan vẫn PENDING, không có bằng chứng CLI/manual test.

## Quyết định
CHANGES REQUIRED. Finding #1 (`.mo` invalid) và #2 (Plan test evidence sai/thiếu) phải khôi phục trước khi merge. Sau khi tác giả fix và push commit mới, bắt buộc re-review phần delta theo `development.md` §6.3 (commit review phải khớp `headRefOid`).

## Re-review
Chưa có (review đầu tiên).

## Checklist hoàn tất review (trước khi merge)
- [ ] File `vc-dev/pr-review/pr-20-i18n-vietnamese.md` tồn tại.
- [ ] `Commit review` khớp `gh pr view 20 --json headRefOid` (hoặc đã có re-review cho commit mới hơn).
- [ ] Verdict cuối `APPROVED` / `APPROVED WITH FOLLOW-UPS`; không còn `BLOCKER`/`MAJOR` OPEN.
- [ ] Mọi follow-up `MINOR`/`NIT` có link file theo dõi (hoặc đã fix).
- [ ] Index `vc-dev/pr-review/README.md` đã cập nhật.
