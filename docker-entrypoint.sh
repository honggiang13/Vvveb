#!/bin/bash
set -e

# Tạo thư mục runtime nếu chưa có (phòng hờ image build thiếu)
mkdir -p \
    storage/cache \
    storage/compiled-templates \
    storage/model \
    public/page-cache \
    config

# Đảm bảo quyền ghi cho www-data
chown -R www-data:www-data storage public/page-cache config public/media public/themes public/image-cache plugins 2>/dev/null || true
chmod -R 775 storage public/page-cache config public/media public/themes public/image-cache plugins 2>/dev/null || true

# Cài Composer dependencies nếu vendor thiếu (first boot hoặc image rebuild)
if [ ! -f vendor/autoload.php ]; then
    echo "Installing Composer dependencies..."
    composer install --no-dev --optimize-autoloader --no-interaction
fi

# Đợi database sẵn sàng (nếu dùng MySQL/MariaDB)
if [ -n "$DB_HOST" ]; then
  for i in {1..30}; do
    if php -r "new mysqli('${DB_HOST}', '${DB_USER}', '${DB_PASSWORD}');" 2>/dev/null; then
      break
    fi
    echo "Waiting for database... ($i/30)"
    sleep 2
  done
fi

# KHÔNG tự chạy CLI install - để user truy cập /install/ để cấu hình thủ công
# Nếu cần auto-install trong CI/CD, set biến AUTO_INSTALL=true
if [ ! -f config/db.php ] && [ "${AUTO_INSTALL:-false}" = "true" ]; then
  echo "AUTO_INSTALL=true -> Running Vvveb CLI install..."
  php cli.php install \
    module=index \
    host="${DB_HOST:-db}" \
    user="${DB_USER:-vvveb}" \
    password="${DB_PASSWORD:-vvveb}" \
    database="${DB_DATABASE:-vvveb}" \
    engine="${DB_ENGINE:-mysqli}" \
    admin[email]="${ADMIN_EMAIL:-admin@vvveb.com}" \
    admin[username]="${ADMIN_USERNAME:-admin}" \
    admin[password]="${ADMIN_PASSWORD:-admin}"
elif [ ! -f config/db.php ]; then
  echo "config/db.php không tồn tại - container sẽ redirect về /install/ khi truy cập web"
  echo "Để auto-install: set AUTO_INSTALL=true trong docker-compose.yml"
fi

# Chạy cron (dọn cache, backup theo lịch)
php cli.php app module=cron || true

# Xóa cache compiled templates để production lấy mã mới
php -r "array_map('unlink', glob('storage/compiled-templates/*'));" 2>/dev/null || true
php -r "array_map('unlink', glob('storage/cache/*'));" 2>/dev/null || true
php -r "array_map('unlink', glob('public/page-cache/*'));" 2>/dev/null || true

exec "$@"