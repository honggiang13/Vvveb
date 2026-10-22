# Multi-language Vietnamese (vi)

- **ID/Ticket:** add Vietnamese language support + translations content prep
- **Branch:** `feature/i18n-vietnamese`
- **Owner hiện tại:** agent
- **Trạng thái tổng:** `PLANNING`
- **Tạo ngày:** 2026-10-21 · **Cập nhật:** 2026-10-21

## Objective

Thêm ngôn ngữ Tiếng Việt (vi) cho site theo đúng chuẩn i18n của Vvveb, chuẩn bị nội dung translations (từ vựng, menu, sản phẩm, blog, dịch vụ) mà không thay đổi codebase không cần thiết. Ưu tiên cấu hình/traductions qua dữ liệu (DB/config/translation files) thay vì sửa core.

Definition of Done:
1. Có file plan tại `vc-dev/features/i18n-vietnamese.md` với đủ cấu trúc.
2. Xác định vị trí translations trong Vvveb (language files/DB) và cách thêm ngôn ngữ vi mà không sửa core bị cấm.
3. Chuẩn bị nội dung dịch Tiếng Việt cho key quan trọng (menu, footer, home, products, blog, services, forms).
4. Không sửa file trong `vendor/**`, `public/js/libs/**`, theme vendor, core routes/config bị cấm.

## Scope & Impact Analysis

- **Module/file ảnh hưởng:** `lang/**` hoặc translations DB (nếu dùng DB), config liên quan ngôn ngữ, `vc-dev/**` (plan/content). Có thể không cần sửa core PHP/JS.
- **Mức can thiệp core (architecture.md §4.2):** mức 1–2 (config/translations) — ưu tiên không sửa core.
- **Giải pháp bảo vệ code core:** chỉ thêm translations/config, không patch core.
- **Rủi ro & giảm thiểu:** cần xác minh cách Vvveb quản lý ngôn ngữ (file-based vs DB). Nếu cần sửa core ⇒ ghi marker + BLOCKED với bằng chứng.
- **Ảnh hưởng khi sync upstream:** không (translations).
- **Ngoài phạm vi (Out of scope):** dịch toàn bộ nội dung tự động, thay đổi UI language switcher logic trừ cấu hình.

## Step-by-Step Tasklist

- [x] COMPLETED: T1 — Khảo sát i18n Vvveb (grep `__()`, `lang/`, `translation`, language config) để xác định cơ chế. (2026-10-21)
- [x] COMPLETED: T2 — Xác định cách thêm ngôn ngữ vi (thêm file lang hoặc cấu hình ngôn ngữ) mà không sửa core. (2026-10-21)
- [x] COMPLETED: T3 — Chuẩn bị bản dịch Tiếng Việt cho key quan trọng (menu, announcement, hero, categories, products, cart/checkout, blog, services, footer, forms, cookie). (2026-10-21)
- [x] COMPLETED: T4 — Đề xuất vị trí lưu translations và quy trình áp dụng (không triển khai code). (2026-10-21)
- [x] COMPLETED: T5 — Cập nhật `vc-dev/features/README.md` và Handoff Summary. (2026-10-21)

## Test Plan

- [x] COMPLETED: Manual — `php debug-parse.php` (project's `phpmo_parse_po_file`) parses `locale/vi_VN/LC_MESSAGES/vvveb.po` → 64 entries (57 translations). (2026-10-22)
- [x] COMPLETED: Manual — `phpmo_convert()` compiles `.po` → `locale/vi_VN/LC_MESSAGES/vvveb.mo` (2924 bytes, valid GNU MO magic `0xDE120495`, contains 57 translations). (2026-10-22)
- [x] COMPLETED: Review — đối chiếu với agent-guide, không có thay đổi core không cần thiết; diff chỉ thêm `locale/vi_VN/*` và `vc-dev/features/*`. (2026-10-22)

## Handoff Summary

### Status Current Phase
- Phase: Implementation hoàn tất (translations added + .mo compiled hợp lệ)
- Đã xong: T1-T5 + compile .mo (2924 bytes, 57 translations)
- Còn lại: None
- Commit cuối: ad9c81e trên `feature/i18n-vietnamese`
- Test đã chạy: `phpmo_parse_po_file` → 64 entries; `phpmo_convert` → `vvveb.mo` 2924 bytes (OK)

### Key Decisions & Findings
1. **Cơ chế i18n:** Vvveb dùng gettext `__()` + admin quản lý translations qua `admin/controller/localization/translations.php`. Files `.po/.mo` lưu tại `locale/{lang}/LC_MESSAGES/` (xem `Locale::setLanguage()` dùng `bindtextdomain` với `DIR_ROOT . 'locale'`, `Locale::installedLanguages()` quét `DIR_ROOT . 'locale/*'`). 
2. **Danh sách ngôn ngữ:** `system/data/languages-list.php` đã có entries `vi` và `vi_VN`.
3. **Đã triển khai:** Tạo `locale/vi_VN/LC_MESSAGES/vvveb.po` với translations quan trọng, compile thành `vvveb.mo` (2924 bytes, 57 translations) bằng `phpmo_convert()` / `phpmo_write_mo_file()` theo logic admin. Lưu ý: `.po` **không BOM** — `system/functions/php-mo.php::phpmo_parse_po_file()` không strip BOM, có BOM sẽ khiến parse trả về false và `.mo` rỗng (28 bytes).
4. **Khuyến nghị:** Để dùng Tiếng Việt, cần cấu hình ngôn ngữ site/người dùng (qua admin/settings). Editor mode bỏ qua translation.

### Context for Next Agent
- **File đã tạo:** `locale/vi_VN/LC_MESSAGES/vvveb.po`, `locale/vi_VN/LC_MESSAGES/vvveb.mo`
- **File CHƯA nên sửa:** `system/**`, `vendor/**`, `public/js/libs/**`, theme vendor
- **Lệnh kiểm tra:** Kiểm tra `Locale::installedLanguages()` sẽ thấy `vi_VN` nếu bootstrap đầy đủ
- **Cạm bẫy:** `Locale::setLanguage()` không áp dụng khi `\Vvveb\isEditor()`

### Next Action Items
1. Cấu hình ngôn ngữ Tiếng Việt cho site (admin/settings hoặc config) nếu muốn hiển thị mặc định.
2. Nếu cần dịch thêm keys, thêm vào `vvveb.po` rồi compile lại hoặc dùng Admin > Localization > Translations.
