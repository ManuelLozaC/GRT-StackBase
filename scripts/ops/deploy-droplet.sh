#!/usr/bin/env bash

set -euo pipefail

APP_PATH="${DEPLOY_APP_PATH:?DEPLOY_APP_PATH is required}"
DEPLOY_REF="${DEPLOY_REF:-main}"
COMPOSE_FILES="-f docker-compose.yml -f docker-compose.prod.yml"

cd "$APP_PATH"

if [ ! -d .git ]; then
  echo "El directorio de despliegue no contiene un repositorio git: $APP_PATH" >&2
  exit 1
fi

mkdir -p backend frontend

if [ -n "${ROOT_ENV_FILE:-}" ]; then
  printf '%s\n' "$ROOT_ENV_FILE" > .env
fi

if [ -n "${BACKEND_ENV_FILE:-}" ]; then
  printf '%s\n' "$BACKEND_ENV_FILE" > backend/.env
fi

if [ -n "${FRONTEND_ENV_FILE:-}" ]; then
  printf '%s\n' "$FRONTEND_ENV_FILE" > frontend/.env
fi

git fetch --all --tags --prune
git checkout "$DEPLOY_REF"
git reset --hard "origin/$DEPLOY_REF" 2>/dev/null || git reset --hard "$DEPLOY_REF"

docker compose $COMPOSE_FILES up -d --build app worker scheduler search db redis
docker compose $COMPOSE_FILES exec -T app composer install --no-interaction --prefer-dist --optimize-autoloader

if ! grep -Eq '^APP_KEY=base64:' backend/.env; then
  docker compose $COMPOSE_FILES exec -T app php artisan key:generate --force
fi

docker compose $COMPOSE_FILES exec -T app php artisan migrate --force
docker compose $COMPOSE_FILES exec -T app php artisan platform:ensure-bootstrap
docker compose $COMPOSE_FILES exec -T app php artisan settings:deduplicate
docker compose $COMPOSE_FILES run --rm frontend sh -c "npm ci && npm run build"
docker compose $COMPOSE_FILES up -d --build web
docker compose $COMPOSE_FILES exec -T app php artisan optimize:clear
docker compose $COMPOSE_FILES exec -T app php artisan optimize
docker compose $COMPOSE_FILES exec -T app php artisan data:reindex-search || true
docker compose $COMPOSE_FILES exec -T app php artisan test --filter=ReleaseSmokeTest
docker compose $COMPOSE_FILES exec -T app php artisan route:list > /dev/null

if [ -n "${HEALTHCHECK_URL:-}" ]; then
  curl --fail --silent --show-error "$HEALTHCHECK_URL" > /dev/null
fi

echo "Despliegue completado correctamente."
