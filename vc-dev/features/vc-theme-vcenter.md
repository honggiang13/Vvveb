# VCenter Theme

- **ID/Ticket:** source design `E:\Personal\VCenter\vcenter-ecommerce\vc-dev\tenant-vcenter\README.md` (UI/UX only)
- **Branch:** `feature/vc-theme-vcenter`
- **Owner hiện tại:** agent
- **Trạng thái tổng:** `PLANNING`
- **Tạo ngày:** 2026-10-21 · **Cập nhật:** 2026-10-21

## Objective

Tạo theme mới `vc-theme-vcenter` cho Vvveb theo thiết kế UI/UX (màu sắc, font, bố cục, hiệu ứng) kế thừa từ tenant VCenter (Odoo `theme_vcenter`), **không** mang theo ghi chú/phụ thuộc của hệ thống cũ (Payload CMS/Next.js). Chỉ áp dụng phần thiết kế vào theme Vvveb.

Definition of Done:
1. Theme mới nằm tại `public/themes/vc-theme-vcenter/` với `index.html`, `theme.json`, assets cần thiết.
2. Theme có thể gán cho site qua `config/sites.php`/admin và render frontend không lỗi.
3. Không sửa theme vendor có sẵn (`landing`, `blog-default`) và không sửa file core bị cấm.
4. `php -l`/smoke test pass; clear cache và render trang chủ + trang sản phẩm.

## Design Specification (Detailed — inherited from VCenter tenant, UI/UX only)

### 1. Brand & Business Context
- Company: CÔNG TY TNHH ĐẦU TƯ VÀ CÔNG NGHỆ GIANG SƠN (VCenter Co., Ltd.)
- Brand: VCenter — Hạ Tầng IT Doanh Nghiệp & Giải Pháp Mạng Chuyên Nghiệp
- Tax/VAT: `0316888999`; base Đà Nẵng, nationwide B2B/B2C
- Hotline: `0236 3730 930` – `0936 007 923`; email `info@vcenter.com.vn`; site `https://odoo.vcenter.com.vn/`
- Positioning: NAS storage, servers, WiFi marketing, switches, CCTV; IT onsite services; B2B/B2C e-commerce with VietQR/bank transfer
- Partners: Synology, Dell, HPE Aruba, EnGenius, Hikvision, EZVIZ

### 2. Design Tokens
Palette:
- `--vc-primary` `#157EBE` (brand & CTA)
- `--vc-primary-hover` `#11689E`
- `--vc-primary-light` `#EBF5FB`
- `--vc-bg-deep` `#0B1329` (announcement bar, dark hero, footer)
- `--vc-accent-cyan` `#06B6D4` (tech highlight, IT quote button, phone icon)
- `--vc-accent-green` `#10B981` (official partner badge)
- `--vc-canvas-bg` `#FFFFFF`
- `--vc-surface-bg` `#F8FAFC`
- `--vc-card-bg` `#FFFFFF`
- `--vc-border-subtle` `#E2E8F0`
- `--vc-text-main` `#0F172A`
- `--vc-text-muted` `#64748B`
- `--vc-price-danger` `#EF4444`

Typography:
- Outfit (500/600/700/800): headings, logo, hero, stats ticker
- Inter (300/400/500/600): body, nav, specs, forms

Geometry:
- Cards/modals: 8px radius (`--radius: 0.5rem`)
- CTA badges: pill/rounded-full
- Shadows: card static `0 1px 3px 0 rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.05)`; card hover `0 12px 28px -4px rgba(21,126,190,0.12), 0 4px 10px -2px rgba(0,0,0,0.04)`; modal `0 25px 50px -12px rgba(15,23,42,0.25)`

### 3. Homepage Layout (7 layers)
1. Top announcement bar: deep navy bg, ~36px, 0.85rem; left partner badge + hotline; right "Cửa Hàng Online" + "Portal Khách Hàng"
2. Sticky header/nav: glassmorphism `rgba(255,255,255,0.95)` + blur(12px), border bottom; logo height 40px; menu Trang Chủ/Sản Phẩm/Dịch Vụ/Tin Tức; search modal, cart badge, hotline, login, CTA "Liên hệ"; mobile offcanvas drawer
3. Hero showcase: 2 columns (55% text / 45% media); tag partners; H1 "Hạ Tầng IT Doanh Nghiệp & Bán Hàng Online Odoo"; description; dual CTA "Mua Sản Phẩm Online" (primary) + "Báo Giá Dịch Vụ IT" (cyan); trust stats 10+ years, 500+ customers, 24/7 support; hero image + floating glassmorphism partner card
4. Category highlight grid: 4 columns responsive (NAS, Workstation, Networking, IT Services) with hover lift and icon color
5. Featured products & tabs: tabs Tất Cả/NAS Synology/Workstation PC/Thiết Bị Mạng/Dịch Vụ IT; product cards (1:1 image, brand tag, title, VND price or "Liên hệ", add-to-cart)
6. IT services 24/7 banner + lead modal: gradient dark container; 4 commitments; CTA "Yêu Cầu Báo Giá IT"; modal fields name/company/phone/service type
7. Enterprise footer: 4 columns (links, about, contact, certifications/payment); copyright © 2026 VCenter Co., Ltd.; cookie consent banner

### 4. Product Groups
- NAS Synology: DiskStation, RackStation, HDD Enterprise
- Server & PC: Dell PowerEdge, Dell Vostro, HP Workstation
- Networking: Aruba Instant On, EnGenius Cloud, switches/routers/SFP
- CCTV: Hikvision HD-TVI/IP, DVR/NVR, EZVIZ
- IT Services: onsite monthly, infrastructure deployment, backup/recovery

### 5. Assets
- `logo-vcenter.svg`, `logo-vcenter-white.svg` (height 40px)
- `hero.webp` (1920x1080 desktop, 800x600 mobile), quality 85%
- `service.webp` (1200x800)
- 4 category images: cat-nas, cat-server, cat-network, cat-services
- Product thumbnails: 1:1, 800x800, white/transparent bg

### 6. Micro-animations
- Staggered entry: top bar 0ms → header 100ms → hero title 200ms → CTA 300ms → hero media 400ms
- Card hover: translateY(-4px), shadow tint, border to primary
- Floating drift: hero partner card translateY(0→-6px→0), 5s infinite
- Button micro-bounce: scale(0.97) 100ms
- Drawer/modal: slide + backdrop blur 8px; modal scale 0.95→1 in 200ms

### 7. Interaction Details
- Search modal full-screen instant typing
- Cart badge updates instantly
- Mobile drawer: hamburger, search bar, large touch targets ≥48px, hotline + CTA full-width
- Service modal: validation, ESC close, toast success "Cảm ơn quý khách! Đội ngũ kỹ thuật VCenter sẽ liên hệ tư vấn trong vòng 15 phút."
- Floating quick contact widget (bottom-right): hotline pulse + Zalo/chat, hide near checkout on mobile
- Cookie consent: show after 1.5s, "Chỉ những thứ cần thiết"/"Tôi đồng ý", persist choice

## Theme Structure, Blocks & Components

### Theme Files
- `public/themes/vc-theme-vcenter/index.html` (base markup)
- `public/themes/vc-theme-vcenter/theme.json` (metadata, tokens)
- `public/themes/vc-theme-vcenter/scss/` (styles)
- `public/themes/vc-theme-vcenter/js/` (interactions)
- `public/themes/vc-theme-vcenter/assets/` (logo, hero, service, categories, product thumbnails)

### Blocks (homepage)
- `AnnouncementBar`: top bar with partners, hotline, portal links
- `HeaderNav`: sticky glassmorphism nav with logo, menu, search, cart, contact CTA, mobile drawer
- `HeroShowcase`: badge, H1, description, dual CTA, trust stats, hero media, floating partner card
- `CategoryGrid`: 4 cards (NAS, Workstation, Networking, IT Services)
- `FeaturedProducts`: filter tabs + product grid
- `ServiceBanner`: IT services 24/7 banner + lead CTA
- `EnterpriseFooter`: 4-column footer + copyright + cookie consent

### Components
- `ProductCard`: 1:1 image, brand tag, title (2 lines), VND price or "Liên hệ", add-to-cart
- `MobileDrawer`: offcanvas navigation
- `SearchModal`: full-screen instant search
- `ServiceRequestModal`: lead form with validation + toast
- `FloatingContactWidget`: hotline pulse + chat
- `CookieConsentBanner`: cookie notice with persist choice

### Pages
- Home: 7-layer layout above
- Product listing: filter tabs + grid
- Product detail: title, price, specs, add-to-cart
- Service request: modal/landing section
- Contact: footer info + form

### Sample Data (manual/seed)
- Categories: NAS Synology, Workstation & Server PC, Networking & WiFi, CCTV, IT Services
- Sample products: DS220j, DS923+, Dell Vostro, Aruba AP22, Hikvision DVR, service packages
- Brand assets and images per design spec

## Scope & Impact Analysis

- **Module/file ảnh hưởng:** `public/themes/vc-theme-vcenter/**` (mới), `config/sites.php` (chỉ gán theme cho site, nếu cần).
- **Mức can thiệp core (architecture.md §4.2):** mức 2 (theme mới) — lý do: tạo theme mới, không sửa theme vendor/core.
- **Giải pháp bảo vệ code core:** theme-only; không patch core.
- **Rủi ro & giảm thiểu:** thiết kế nguồn thuộc hệ thống khác; chỉ kế thừa UI/UX (tokens, layout, micro-animations), bỏ mọi phụ thuộc Payload/Next.js/seed.
- **Ảnh hưởng khi sync upstream:** không.
- **Ngoài phạm vi (Out of scope):** port seed/multi-tenant, backend Payload, import dữ liệu sản phẩm, plugin import CSV.

## Step-by-Step Tasklist

- [ ] PENDING: T1 — Khảo sát cấu trúc theme Vvveb hiện có (index.html, theme.json, scss, gulpfile) để làm mẫu.
- [ ] PENDING: T2 — Tạo thư mục `public/themes/vc-theme-vcenter/` với bộ khung theme mới.
- [ ] PENDING: T3 — Áp dụng design tokens VCenter (màu, font Outfit/Inter, radius, shadow) vào theme.
- [ ] PENDING: T4 — Dựng layout trang chủ 7 tầng (announcement bar, header, hero, category grid, featured products, service banner, footer) theo thiết kế UI.
- [ ] PENDING: T5 — Thêm micro-animations và responsive theo thiết kế.
- [ ] PENDING: T6 — Gán theme cho site và smoke test frontend/admin; clear cache.
- [ ] PENDING: T7 — Cập nhật `vc-dev/features/README.md` và Handoff Summary.

## Test Plan

- [ ] PENDING: Manual — render trang chủ + trang sản phẩm, kiểm tra responsive desktop/tablet/mobile.
- [ ] PENDING: CLI/automation — `php -l` (nếu có PHP liên quan), clear compiled templates/cache, kiểm tra không lỗi console.

## Data Seed Content (crawled from odoo.vcenter.com.vn)

### Categories
- NAS Synology (DS220j, DS923+, RackStation)
- Workstation & PC (Dell Vostro, HP, máy trạm)
- Thiết Bị Mạng & WiFi (Aruba, Cisco, Mikrotik, EnGenius)
- Dịch Vụ IT Onsite (bảo trì LAN/WiFi/Camera 24/7)

### Featured Products (seed samples)
- Synology DiskStation DS220j — 5.379.000 ₫ (NAS Synology)
- Máy Bộ Dell Vostro 3471ST G5420 — 6.535.000 ₫ (Workstation & PC)
- Bộ Phát WiFi 6 Aruba AP505 / Instant On AP22 — 4.850.000 ₫ (Thiết Bị Mạng & WiFi)
- Gói Bảo Trì IT Onsite Doanh Nghiệp (Theo Tháng) — 3.500.000 ₫ (Dịch Vụ IT Onsite)
- Synology DiskStation DS923+ (4-Bay) — 16.490.000 ₫ (NAS Synology)
- Máy Bộ PC Dell Vostro 3671ST Core i3-9100 — 7.254.000 ₫ (Workstation & PC)
- DS-7204/7208HUHI-K1 Turbo HD DVR — Liên hệ
- DS-7204/7208/7216HQHI-K1 Turbo HD DVR — Liên hệ
- Đầu ghi Turbo HD DVR (TVI 1MP/analog/IP) — Liên hệ
- Đầu ghi NVR 8/16 kênh — Liên hệ

### Copywriting (seed content)
- Hero: "Hạ Tầng IT Doanh Nghiệp & Bán Hàng Online Odoo"; description VCenter giải pháp tổng thể NAS Synology, Server Dell, WiFi Aruba, dịch vụ IT onsite, mua sắm trực tuyến tích hợp Odoo ERP
- Trust stats: 10+ năm kinh nghiệm, 500+ khách hàng doanh nghiệp, 24/7 hỗ trợ IT onsite
- Service commitments: IT Helpdesk Onsite & Remote, bảo trì LAN/WiFi/Server, sao lưu NAS & khắc phục sự cố 30 phút, cam kết SLA 99.9%
- About: đội ngũ đam mê cải thiện cuộc sống qua sản phẩm đột phá; phục vụ doanh nghiệp vừa và nhỏ
- Contact: email info@vcenter.com.vn, hotline 0236 3730 930, social Facebook/Twitter/LinkedIn
- Cookie: "Chúng tôi sử dụng cookie để mang đến cho bạn trải nghiệm người dùng tốt hơn trên trang web này."

### Images (seed assets)
- Logo: `/web/image/website/1/logo/VCenter`
- Hero: `/theme_vcenter/static/src/img/hero.jpg`
- Service: `/theme_vcenter/static/src/img/service.jpg`
- Product images: `/web/image/product.template/<id>/image_1920`

### Blog Categories (seed content)
- Tất cả
- Tin Tức
- Dịch vụ CNTT
- Dự án tiêu biểu
- Giải pháp của VCenter

### Blog Posts (seed samples)
- Giới thiệu giải pháp wifi của UniFi — UniFi tiết kiệm thời gian/chi phí nhờ phần mềm quản lý toàn diện
- Giới thiệu giải pháp camera của HIKVISION — nhà cung cấp camera an ninh hàng đầu, Safe City Singapore 100k camera IP
- Giới thiệu giải pháp camera của DAHUA — top nhà cung cấp giám sát hình ảnh/an ninh
- Giới thiệu giải pháp mạng của RUIJIE — nhà cung cấp thiết bị mạng doanh nghiệp hàng đầu Trung Quốc, số 1 WLAN, số 3 Switch
- Giới thiệu giải pháp camera của EZVIZ — công ty con Hikvision, thành lập 2016
- Lắp đặt camera tại Malthop Việt Nam — dự án 2019, quy trình 7 bước
- Lắp đặt camera cho LAM Coffee — 2021, sản phẩm chính hãng bảo hành 2 năm
- Lắp đặt camera tại Tổ dân phố Bình Hòa 1 — Đà Nẵng, 2018
- Lắp đặt camera, mạng, wifi, iptv cho Homestay tại Mỹ An — camera HIKVISION 1080P, UniFi WIFI, IPTV VNPT
- Lắp đặt wifi Unifi tại chuỗi Đông Á Spa — 293 Hùng Vương, 2019, bảo hành 1 năm
- Lắp đặt hệ thống mạng tại Nhà hàng Việt — UniFi WIFI AP AC PRO + Mikrotik, 2019

### Services Page Content (seed)
- Title: "Thiết kế Shopdrawing điện nhẹ & hạ tầng mạng thông minh"
- Intro: VCENTER cung cấp giải pháp từ khảo sát, thiết kế, triển khai đến cấu hình và bàn giao cho hệ thống mạng LAN/WiFi, Camera, kiểm soát ra vào, âm thanh thông báo, IPTV/SMATV, điện thoại nội bộ và hạ tầng cáp quang
- Highlights: 01 hồ sơ kỹ thuật rõ ràng, 02 triển khai đồng bộ, 03 tối ưu theo công trình
- Service groups:
  1. Thiết kế Shopdrawing hệ thống điện nhẹ: mạng LAN & WiFi, camera giám sát, kiểm soát ra vào, âm thanh thông báo, IPTV/SMATV & điện thoại; outputs bản vẽ bố trí, sơ đồ nguyên lý, triển khai, hoàn công As-Built, chi tiết lắp đặt, BOQ
  2. Hạ tầng mạng thông minh cho căn hộ & chung cư: cáp quang FTTH, WiFi toàn căn hộ (WiFi 6/6E/7), mạng LAN Cat6/Cat6A, camera IP, quản lý mạng Firewall/Gateway/Switch/NVR
- Integrated systems: mạng LAN & WiFi, camera giám sát, kiểm soát ra vào, âm thanh thông báo, IPTV/SMATV, điện thoại nội bộ
- Process 4 steps: tiếp nhận yêu cầu → triển khai hồ sơ → hiệu chỉnh → bàn giao (CAD, PDF, BOQ, hoàn công)
- Value: giảm lỗi thi công, tiết kiệm chi phí, dễ nghiệm thu, chuẩn hóa tài liệu, tăng hiệu quả vận hành, triển khai nhanh chóng
- Contact: hotline 0936 007 923, email info@vcenter.com.vn, website www.vcenter.com.vn
- Company footer: "CÔNG TY TNHH ĐẦU TƯ VÀ CÔNG NGHỆ GIANG SƠN (VCENTER) — Vùng chất lượng, trọn niềm tin."

## Handoff Summary

(chưa có — điền khi kết thúc phase)
