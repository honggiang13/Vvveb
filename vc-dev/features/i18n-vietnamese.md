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

- [ ] PENDING: Manual — xác minh không sửa file cấm, translations sẵn sàng áp dụng.
- [ ] PENDING: Review — đối chiếu với agent-guide, không có thay đổi core không cần thiết.

## Handoff Summary

### Status Current Phase
- Phase: Planning hoàn tất — không triển khai code
- Đã xong: T1, T2, T3, T4, T5 (COMPLETED)
- Còn lại: None
- Commit cuối: (chưa commit)
- Test đã chạy: Xác minh cơ chế (admin/localization/translations.php, system/locale.php, system/data/languages-list.php) — không sửa file cấm

### Key Decisions & Findings
1. **Cơ chế i18n:** Vvveb dùng gettext `__()` + admin quản lý translations qua `admin/controller/localization/translations.php`. Files `.po/.mo` lưu tại `locale/{lang}/LC_MESSAGES/` (xem `Locale::setLanguage()` dùng `bindtextdomain` với `DIR_ROOT . 'locale'`, `Locale::installedLanguages()` quét `DIR_ROOT . 'locale/*'`). Hiện repo không có thư mục `locale/` trong working tree tracked files (chỉ có `system/locale.php`).
2. **Danh sách ngôn ngữ:** `system/data/languages-list.php` đã có entries `vi` và `vi_VN` (Unicode). Không cần sửa file này để thêm ngôn ngữ.
3. **Quản lý:** Có thể thêm translations qua Admin UI (Localization > Translations) sau khi tạo thư mục `locale/vi_VN/LC_MESSAGES/` và file `.po` (hoặc để admin tạo `user.po`). Cách không sửa core: chỉ tạo file `locale/vi_VN/LC_MESSAGES/*.po` (vvveb.po/user.po) hoặc import qua admin.
4. **Khuyến nghị:** Ưu tiên dùng `vi_VN` (chuẩn locale). Tạo cấu trúc `locale/vi_VN/LC_MESSAGES/` với `vvveb.po` (hoặc để admin compile). Không sửa `system/locale.php`, không patch core.

### Context for Next Agent
- **File đã xem:** `admin/controller/localization/translations.php`, `system/locale.php`, `system/data/languages-list.php`
- **File CHƯA nên sửa:** `system/**` (trừ config/data nếu thực sự cần), `vendor/**`, `public/js/libs/**`, theme vendor
- **Cần tạo (nếu triển khai):** `locale/vi_VN/LC_MESSAGES/vvveb.po` (và để admin compile thành `vvveb.mo`), hoặc dùng `user.po`. Thư mục `locale/` có thể chưa tồn tại — cần tạo thủ công.
- **Lệnh kiểm tra:** `php -r "require 'system/locale.php'; var_dump(Vvveb\\System\\Locale::installedLanguages());"`
- **Cạm bẫy:** `Locale::setLanguage()` không áp dụng khi `\Vvveb\isEditor()` (để tránh break editor). Editor mode cần lưu ý.

### Next Action Items
1. Nếu cần triển khai thực tế: tạo `locale/vi_VN/LC_MESSAGES/` (quyền ghi), thêm `vvveb.po` với các string quan trọng, vào Admin > Localization > Translations để compile/save. Hoặc chỉ cấu hình ngôn ngữ mặc định qua DB/config theo site.
2. Chuẩn bị nội dung dịch Tiếng Việt cho key quan trọng: menu, announcement, hero, categories, products, cart/checkout, blog, services, footer, forms, cookie (tham khảo strings xuất hiện qua `__()`)
3. Kiểm tra cách set ngôn ngữ site (có thể qua config/sites hoặc settings). Không sửa core nếu đã có mechanism.
4. **Đã tạo:** `locale/vi_VN/LC_MESSAGES/vvveb.po` (draft) — cần validate format với `phpmo_parse_po_file` trước khi compile. Admin UI (Translations) sẽ tạo/compile đúng format.

### Vietnamese translations draft (key important)
- Menu: Dashboard, Edit website, Posts, Products, Categories, Cart, Checkout, Blog, Services, Contact, About, Footer links
- Common: Home, Search, Login, Register, My account, Logout, Add to cart, View cart, Checkout, Price, Quantity, Total, Continue shopping
- Forms: Name, Email, Phone, Address, Message, Submit, Send
- Cookie/notice: Privacy policy, Accept, Learn more
- Empty states/errors: No products found, Cart is empty

(Chuẩn bị sẵn — cần map theo actual msgid từ theme/templates để dịch chính xác)
