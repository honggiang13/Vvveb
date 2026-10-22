# PR #20 — `feature/i18n-vietnamese`

- **Reviewer:** independent session (Kilo agent, context sạch, khác tác giả PR)
- **Ngày:** 2026-10-22
- **Base:** `vc-dev` · **Commit review:** `7bbe9023e1904765201913b70a144741dbd08cd0`
- **Verdict ban đầu:** CHANGES REQUIRED
- **Verdict cuối:** APPROVED WITH FOLLOW-UPS

## Findings

### 1. MAJOR — `locale/vi_VN/LC_MESSAGES/vvveb.mo` là file rỗng/không hợp lệ (28 bytes, không chứa translation)
- **Vị trí:** `locale/vi_VN/LC_MESSAGES/vvveb.mo` (ADDED, 28 bytes)
- **Vấn đề:** File `.mo` chỉ có header magic (`0xDE120495`) rồi toàn byte 0, không chứa mục translation nào. Có 57 msgid trong `.po` nhưng `.mo` compile ra không có translation ⇒ gettext sẽ không dịch được gì cả. PR description claim "compiled via phpmo_write_mo_file" nhưng file `.mo` thực tế không chứa dữ liệu dịch.
- **Bằng chứng:** `git show 20:locale/vi_VN/LC_MESSAGES/vvveb.mo` kích thước 28 bytes; hexdump header `de 12 04 95` rồi toàn `00`; table offsets/count đều 0.
- **Fix:** Strip UTF-8 BOM khỏi `.po` (`system/functions/php-mo.php::phpmo_parse_po_file()` không strip BOM ⇒ parse trả về false ⇒ `.mo` rỗng), rồi `phpmo_convert()` biên dịch lại. `.mo` mới 2924 bytes, magic `0x950412de`, 64 entries decode đúng (ví dụ `About => Giới thiệu`).
- **Trạng thái:** FIXED (commit `7bbe902`)

### 2. MINOR — Plan file `vc-dev/features/i18n-vietnamese.md` đánh giá tự cao, Test Plan còn PENDING
- **Vị trí:** `vc-dev/features/i18n-vietnamese.md` (Test Plan + Handoff Summary)
- **Vấn đề:** Handoff Summary ghi "Test đã chạy: Compile vvveb.mo bằng phpmo_write_mo_file qua logic admin (OK)" nhưng Test Plan mục "Manual — xác minh không sửa file cấm, translations sẵn sàng áp dụng" vẫn ở `PENDING`; claim compile OK bị mâu thuẫn với finding #1 (file `.mo` invalid). Vì không có test framework nên cần bằng chứng thủ công rõ ràng.
- **Fix:** Cập nhật Test Plan thành COMPLETED kèm lệnh xác minh thực tế (`phpmo_parse_po_file` → 64 entries; `phpmo_convert` → `.mo` 2924 bytes); sửa Handoff Summary khớp thực tế.
- **Trạng thái:** FIXED (commit `7bbe902`)

### 3. NIT — `.po` file bắt đầu có UTF-8 BOM
- **Vị trí:** `locale/vi_VN/LC_MESSAGES/vvveb.po:1`
- **Vấn đề:** Byte đầu là `239 187 191` (`EF BB BF` = UTF-8 BOM). gettext xử lý được BOM nhưng `phpmo_parse_po_file()` của dự án không strip BOM, gây lỗi parse. Không chặn merge, chỉ ghi nhận.
- **Trạng thái:** REJECTED — chấp nhận (BOM không phải lỗi gettext). Đồng thời `.po` đã được strip BOM trong commit fix, làm tốt hơn yêu cầu.

## Quyết định
APPROVED WITH FOLLOW-UPS. Tất cả `BLOCKER`/`MAJOR` đã FIXED; `MINOR` đã FIXED; `NIT` đã REJECTED (chấp nhận). Không còn finding OPEN. Có thể merge khi head SHA khớp commit review `7bbe902`.

## Re-review 1
- **Head SHA:** `7bbe9023e1904765201913b70a144741dbd08cd0`
- **Kết quả:**
  - Finding #1 (MAJOR, `.mo` invalid): FIXED — `.mo` mới 2924 bytes, magic `0x950412de`, 64 entries decode đúng.
  - Finding #2 (MINOR, Plan test evidence): FIXED — Test Plan COMPLETED + Handoff Summary khớp thực tế.
  - Finding #3 (NIT, BOM): REJECTED — chấp nhận; đồng thời `.po` đã được strip BOM làm tốt hơn yêu cầu.
- **Xác minh lại:** decode `.mo` thành công 64 entries; parse `.po` → 64 entries.

## Claim đã xác minh đúng
- Claim "Files `.po/.mo` lưu tại `locale/{lang}/LC_MESSAGES/`" — ĐÚNG: `system/locale.php:147` dùng `bindtextdomain($domain, DIR_ROOT . 'locale')`; `system/functions.php:529` dùng `DIR_ROOT . 'locale' . DS . $lang . DS 'LC_MESSAGES' . DS`.
- Claim "`system/data/languages-list.php` đã có entries `vi` và `vi_VN`" — ĐÚNG: dòng 2475 (`vi_VN`) và 2481 (`vi`).
- Claim không sửa file cấm (vendor, core PHP/JS) — ĐÚNG: diff chỉ thêm `locale/vi_VN/*`, `vc-dev/features/i18n-vietnamese.md`, sửa `vc-dev/features/README.md`; không có file vendor/core trong diff.
- Claim `.po` có 201 dòng / 57 msgid — ĐÚNG (đếm trực tiếp file).

## Claim không xác minh được
- Claim "Compile vvveb.mo bằng phpmo_write_mo_file qua logic admin (OK)" — ĐÃ XÁC MINH LẠI SAU FIX: commit `7bbe902` chạy `phpmo_convert()` thành công, `.mo` 2924 bytes hợp lệ.
- Claim test đã chạy + OK — ĐÃ XÁC MINH LẠI SAU FIX: Test Plan COMPLETED kèm lệnh + kết quả thực tế.

## Checklist hoàn tất review (trước khi merge)
- [x] File `vc-dev/pr-review/pr-20-i18n-vietnamese.md` tồn tại.
- [x] `Commit review` khớp `headRefOid` PR (`7bbe902`).
- [x] Verdict cuối `APPROVED WITH FOLLOW-UPS`; không còn `BLOCKER`/`MAJOR` OPEN.
- [x] Mọi follow-up `MINOR`/`NIT` đã fix hoặc REJECTED.
- [x] Index `vc-dev/pr-review/README.md` đã cập nhật.
