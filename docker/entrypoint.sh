#!/bin/sh
set -e

cd /app

# Railway public domain → canonical HTTPS URL (verification emails, Google OAuth redirect URI)
if [ -n "${RAILWAY_PUBLIC_DOMAIN:-}" ]; then
  export APP_URL="https://${RAILWAY_PUBLIC_DOMAIN}"
elif [ -z "${APP_URL:-}" ]; then
  export APP_URL="http://127.0.0.1:8000"
fi

export APP_PUBLIC_URL="${APP_PUBLIC_URL:-$APP_URL}"
export DEFAULT_URI="${DEFAULT_URI:-$APP_URL}"

# Ensure Symfony has a .env file (image may only ship .env.example)
if [ ! -f .env ]; then
  if [ -f .env.example ]; then
    cp .env.example .env
  elif [ -f .env.baked ]; then
    cp .env.baked .env
  fi
fi

PORT="${PORT:-8000}"

# Writable nginx site config (avoid sed on read-only bind mounts)
NGINX_SITE="/tmp/nginx-default.conf"
if [ -f /etc/nginx/sites-available/default.conf ]; then
  cp /etc/nginx/sites-available/default.conf "$NGINX_SITE"
else
  cp /etc/nginx/sites-enabled/default.conf "$NGINX_SITE"
fi
sed -i "s/listen 8000;/listen ${PORT};/" "$NGINX_SITE"
sed -i "s/listen \[::\]:8000;/listen [::]:${PORT};/" "$NGINX_SITE" 2>/dev/null || true

if [ -f vendor/autoload_runtime.php ]; then
  if [ ! -f config/jwt/private.pem ] || [ ! -f config/jwt/public.pem ]; then
    echo "Generating JWT key pair..."
    php bin/console lexik:jwt:generate-keypair --skip-if-exists 2>/dev/null || true
  fi

  if [ "${APP_ENV:-prod}" = "prod" ]; then
    php bin/console cache:clear --no-warmup 2>/dev/null || true
    php bin/console cache:warmup 2>/dev/null || true
    php bin/console asset-map:compile --no-interaction 2>/dev/null || true
  fi

  if [ -n "${DATABASE_URL:-}" ]; then
    echo "Running database migrations..."
    if ! php bin/console doctrine:migrations:migrate --no-interaction; then
      echo "ERROR: doctrine:migrations:migrate failed — admin pages may 500 until migrations succeed."
    fi
  fi

  # PHP-FPM runs as www-data; console above runs as root
  chown -R www-data:www-data var config/jwt public/uploads 2>/dev/null || true
fi

php-fpm -D
exec nginx -g 'daemon off;' -c /etc/nginx/nginx.conf
