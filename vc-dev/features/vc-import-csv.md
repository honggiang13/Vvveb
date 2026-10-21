# VCenter Import CSV

- **ID/Ticket:** source `E:\Personal\VCenter\VCenter-Products\output\vcenter-vvveb\vvveb-plugins\vc-import-csv`
- **Branch:** `feature/vc-import-csv`
- **Owner hiện tại:** agent
- **Trạng thái tổng:** `PLANNING`
- **Tạo ngày:** 2026-10-21 · **Cập nhật:** 2026-10-21

## Objective

Port plugin `vc-import-csv` từ thư mục source vào core repo dưới `plugins/vc-import-csv/**` theo đúng quy ước plugin của dự án, cho phép admin import sản phẩm/danh mục/vendor/ảnh/biến thể/meta/SEO từ CSV (WooCommerce, Shopify, Magento, PrestaShop, OpenCart, custom).

Definition of Done:
1. Plugin activate/deactivate qua CLI không lỗi.
2. Admin page `plugins/vc-import-csv/settings` load được, upload/config/import chạy được.
3. Không sửa file core thuộc danh sách cấm; mọi thay đổi nằm trong `plugins/vc-import-csv/**`.
4. `php -l` pass cho mọi file PHP của plugin.

## Scope & Impact Analysis

- **Module/file ảnh hưởng:** `plugins/vc-import-csv/**` (mới), `public/plugins/vc-import-csv/**` (copy khi activate).
- **Mức can thiệp core (architecture.md §4.2):** mức 1 (plugin) — lý do: plugin độc lập, đăng ký menu qua `Event::on('Vvveb\Controller\Base', 'init-menu')`, không cần sửa core.
- **Giải pháp bảo vệ code core:** plugin-only; không sửa `config/*-routes.php`, không patch core.
- **Rủi ro & giảm thiểu:** source plugin dùng namespace `Vvveb\Plugins\VcImportCsv\...` và tham chiếu `DIR_BACKUP`, `DIR_SYSTEM . 'import' . DS . 'table-order.php'`, `Vvveb\Plugins\VcImportCsv\Provider\...`; cần xác minh các hằng/symbol này tồn tại trong core trước khi port. Nếu thiếu ⇒ ghi BLOCKED + câu hỏi.
- **Ảnh hưởng khi sync upstream:** không (plugin tự chứa).
- **Ngoài phạm vi (Out of scope):** vendor refresh theme/admin; sửa core import table-order; thêm provider mới ngoài source.

## Step-by-Step Tasklist

- [ ] PENDING: T1 — Khảo sát core: xác minh `DIR_BACKUP`, `DIR_SYSTEM`, `DS`, `adminPath()`, `PUBLIC_PATH`, `V_VERSION`, `APP`, `table-order.php`, model compile conventions; grep usage.
- [ ] PENDING: T2 — Sao chép toàn bộ source `vc-import-csv` vào `plugins/vc-import-csv/` (plugin.php, admin, provider, public, svg) đúng cấu trúc §3.1.
- [ ] PENDING: T3 — Chuẩn hóa metadata doc-comment theo quy ước (Name/Description/Version/Author/Category/Settings) và namespace `Vvveb\Plugins\VcImportCsv`.
- [ ] PENDING: T4 — Kiểm tra/điều chỉnh admin controller namespace, route module `plugins/vc-import-csv/settings`, template path; đảm bảo không `die()` trong flow trừ upload/redirect đã có.
- [ ] PENDING: T5 — `php -l` tất cả file PHP plugin; sửa lỗi syntax.
- [ ] PENDING: T6 — Activate/deactivate qua CLI và smoke admin/frontend; clear cache.
- [ ] PENDING: T7 — Cập nhật `vc-dev/features/README.md` bảng danh sách tính năng và Handoff Summary.

## Test Plan

- [ ] PENDING: Manual — activate plugin, mở admin settings, upload CSV, config mapping, chạy import mẫu, kiểm tra sản phẩm.
- [ ] PENDING: CLI/automation — `php cli.php admin module=plugin/plugins action=checkPluginAndActivate plugin=vc-import-csv`; `php cli.php admin module=plugin/plugins action=deactivate plugin=vc-import-csv`; `php -l` các file.

## Handoff Summary

(chưa có — điền khi kết thúc phase)
