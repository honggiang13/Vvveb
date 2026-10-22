#!/bin/bash
# Fresh install reset cho Vvveb
# Xóa cache, config db, config site để đưa site về trạng thái mới
# Sử dụng: ./fresh-install.sh [--keep-storage] [--keep-page-cache]

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

KEEP_STORAGE=0
KEEP_PAGE_CACHE=0

while [[ $# -gt 0 ]]; do
  case "$1" in
    --keep-storage) KEEP_STORAGE=1; shift ;;
    --keep-page-cache) KEEP_PAGE_CACHE=1; shift ;;
    --help|-h)
      echo "Sử dụng: $0 [--keep-storage] [--keep-page-cache]"
      echo "  --keep-storage      Giữ nguyên storage (không xóa compiled-templates/cache/logs/backup/sqlite)"
      echo "  --keep-page-cache   Giữ nguyên public/page-cache"
      exit 0
      ;;
    *)
      echo "Tùy chọn không xác định: $1" >&2
      exit 1
      ;;
  esac
done

echo "=== Bắt đầu reset site Vvveb về fresh install ==="

# 1. Xóa config db (để cài lại DB)
DB_CONFIG="config/db.php"
if [[ -f "$DB_CONFIG" ]]; then
  echo "!! CẢNH BÁO: xóa $DB_CONFIG ⇒ site sẽ mở installer cho công chúng cho tới khi cài xong."
  echo "   Sau khi cài lại phải chặn truy cập /install/ (vd: đổi tên thư mục install hoặc chặn ở reverse proxy)."
  rm -f "$DB_CONFIG"
  echo "✓ Đã xóa $DB_CONFIG"
else
  echo "✓ $DB_CONFIG không tồn tại (bỏ qua)"
fi

# 2. Xóa config site (multi-site config nếu có)
SITES_CONFIG="config/sites.php"
if [[ -f "$SITES_CONFIG" ]]; then
  rm -f "$SITES_CONFIG"
  echo "✓ Đã xóa $SITES_CONFIG"
  echo "  (file này được git track - trong checkout dev dùng 'git checkout config/sites.php' để khôi phục)"
else
  echo "✓ $SITES_CONFIG không tồn tại (bỏ qua)"
fi

# 3. Xóa các folder cache trong storage
if [[ "$KEEP_STORAGE" -eq 0 ]]; then
  for dir in storage/cache storage/compiled-templates storage/logs storage/backup storage/sqlite storage/model; do
    if [[ -d "$dir" ]]; then
      rm -rf "$dir"
      echo "✓ Đã xóa folder $dir"
    else
      echo "✓ $dir không tồn tại (bỏ qua)"
    fi
  done
else
  echo "✓ Giữ nguyên storage theo yêu cầu --keep-storage"
fi

# 4. Xóa page-cache
if [[ "$KEEP_PAGE_CACHE" -eq 0 ]]; then
  if [[ -d "public/page-cache" ]]; then
    rm -rf public/page-cache/*
    echo "✓ Đã xóa nội dung public/page-cache"
  else
    echo "✓ public/page-cache không tồn tại (bỏ qua)"
  fi
else
  echo "✓ Giữ nguyên public/page-cache theo yêu cầu --keep-page-cache"
fi

# 5. Xóa assets-cache và image-cache nếu có
for dir in public/assets-cache public/image-cache; do
  if [[ -d "$dir" ]]; then
    rm -rf "$dir"/*
    echo "✓ Đã xóa nội dung $dir"
  fi
done

echo ""
echo "=== Đã xong reset site về fresh install ==="
echo "Mở trang web để chạy installer (form cài đặt) và điền thông tin DB."
echo "Lưu ý: trong khi chưa cài xong, /install/ đang mở cho công chúng - hãy chặn truy cập nếu site đã chạy production."
