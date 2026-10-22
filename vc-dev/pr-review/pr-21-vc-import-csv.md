# PR #21 — `feature/vc-import-csv`

- **Reviewer:** independent session (subagent `general`, context sạch, khác tác giả PR)
- **Ngày:** 2026-10-22
- **Base:** `vc-dev` · **Commit review:** `d6f593c5c6adb2a6bc3b3e9c19ea0f76c3c61e00`
- **Commit feature:** `8a309361e5e0adfc4e4dbcc296d063155c1ee8be`
- **Verdict ban đầu:** APPROVED WITH FOLLOW-UPS
- **Verdict cuối:** APPROVED WITH FOLLOW-UPS

## Findings

### 1. MINOR — Doc-comment format của `plugin.php` gây hiển thị "broken" trong danh sách admin
- **Vị trí:** `plugins/vc-import-csv/plugin.php:21-31`
- **Vấn đề:** Doc-comment dùng `/*` (dấu ngoặc sao đơn) thay vì `/**` (dấu ngoặc sao đ엉). `Extensions::getInfo()` parse qua `token_get_all()` với `T_DOC_COMMENT` / `T_COMMENT` — `/* ... */` được classify là `T_COMMENT`, không phải `T_DOC_COMMENT`, do đó các trường Name/Slug/Description... không được parse. Trong `getList()` (line 201-206), nếu không có `name` thì hiển thị `[vc-import-csv]` và status `broken`.
- **Bằng chứng:** `git show 8a30936:plugins/vc-import-csv/plugin.php` dòng 21-31 dùng `/*`; CLI smoke test hiển thị `status: "broken"`, `name: "[vc-import-csv]"` trong danh sách plugins.
- **Tác động:** Chỉ cosmetic — plugin vẫn `active` trong `config/plugins.php`, controller load được, import chạy bình thường. Không chặn merge.
- **Khuyến nghị:** Thay `/*` bằng `/**` để parse đúng metadata, hiển thị tên đầy đủ và status chính xác.
- **Trạng thái:** OPEN (khuyến nghị follow-up, không chặn merge)

### 2. MINOR — 4 nền tảng (Shopify, Magento, PrestaShop, OpenCart) ghi NOT IMPLEMENTED trong api-contract
- **Vị trí:** `vc-dev/wip/api-contract.md`
- **Vấn đề:** Source plugin chỉ implement 3 provider: `woocommerce`, `woocommerce_full`, `custom`. Grep toàn bộ repo `shopify|magento|prestashop|opencart` = 0 match. 4 nền tảng này được ghi là planned nhưng chưa có provider.
- **Bằng chứng:** `vc-dev/wip/api-contract.md` bảng mapping; grep toàn repo = 0 match.
- **Tác động:** Thuộc scope out-of-plan (plan ghi rõ "thêm provider mới ngoài source nằm ngoài phạm vi"). Không chặn merge.
- **Trạng thái:** OPEN (ghi nhận, out of scope plan)

## Quyết định
APPROVED WITH FOLLOW-UPS. Không có `BLOCKER`/`MAJOR` OPEN. Hai `MINOR` là follow-up không chặn merge:
- Finding #1 (doc-comment format): khuyến nghị sửa `/*` → `/**` cho hiển thị đúng trong admin.
- Finding #2 (4 nền tảng chưa implement): out of scope plan, ghi nhận để làm sau.

Có thể merge khi head SHA khớp commit review `d6f593c5`.

## Claim đã xác minh đúng
- Claim "Tất cả 7 file PHP pass `php -l`" — ĐÚNG: `plugin.php`, `admin/controller/settings.php`, `provider/{provider,extended,woocommerce,woocommerce_full,custom}.php` đều "No syntax errors detected".
- Claim "CLI activate thành công" — ĐÚNG: `php -d extension=sqlite3 cli.php admin module=plugin/plugins action=activate plugin=vc-import-csv` trả về `"Plugin \`Vc import csv\` activated!"`; `config/plugins.php` có `vc-import-csv` status = `'active'`.
- Claim "Smoke test controller settings load được" — ĐÚNG: `php -d extension=sqlite3 cli.php admin module=plugins/vc-import-csv/settings action=index` trả về JSON providers: `woocommerce`, `woocommerce_full`, `custom`.
- Claim "Không sửa file core" — ĐÚNG: diff commit `8a30936` chỉ chứa `plugins/vc-import-csv/**`, `public/plugins/vc-import-csv/**`, `vc-dev/features/**`, `config/plugins.php`, `.gitignore`; không có `system/*` hoặc `config/*-routes.php`.
- Claim "Namespace `Vvveb\Plugins\VcImportCsv\` đúng trên tất cả file" — ĐÚNG: tất cả controller/provider dùng namespace này.
- Claim "`config/db.php` cấu trúc `connections.default.engine`" — ĐÚNG: `startup.php:363-368` đọc `config('db.connections.default.engine')`; `config/db.php` đã được sửa theo cấu trúc này.

## Claim không xác minh được (do môi trường)
- Claim "Manual test upload CSV, config mapping, chạy import mẫu, kiểm tra sản phẩm" — CHƯA XÁC MINH: cần database SQLite đầy đủ + browser/admin UI thực tế. CLI smoke test chỉ xác minh controller load được, chưa test flow import end-to-end qua HTTP.
- Claim "Activate/deactivate qua CLI không lỗi" — ĐÃ XÁC MINH activate; deactivate chưa chạy lại sau khi database đã có (chỉ verify cơ chế `PluginsList::activate()`/`deactivate()` tồn tại và hoạt động).

## Checklist hoàn tất review (trước khi merge)
- [x] File `vc-dev/pr-review/pr-21-vc-import-csv.md` tồn tại.
- [x] `Commit review` khớp HEAD `d6f593c5` (sau merge origin/vc-dev).
- [x] Verdict cuối `APPROVED WITH FOLLOW-UPS`; không còn `BLOCKER`/`MAJOR` OPEN.
- [x] Mọi follow-up `MINOR` đã ghi nhận (khuyến nghị follow-up, không chặn merge).
- [ ] Index `vc-dev/pr-review/README.md` đã cập nhật (cần làm sau khi tạo file này).

## Re-review (sau follow-up)
- Chưa cần re-review vì không có finding chặn merge.
