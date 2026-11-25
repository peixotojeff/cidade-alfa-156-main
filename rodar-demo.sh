#!/usr/bin/env bash
set -euo pipefail
root=$(cd -- "$(dirname -- "$0")" && pwd)
cd "$root/app"
if [[ -x "$root/.tooling/frankenphp-stable" ]]; then
  php_cmd=("$root/.tooling/frankenphp-stable" php-cli)
else
  php_cmd=(php)
fi
umask 077
export APP_ENV=local APP_DEBUG=false APP_URL=http://127.0.0.1:8156 CIDADE_DEMO=true
export DB_CONNECTION=sqlite DB_DATABASE="$root/app/database/demo.sqlite" SESSION_DRIVER=file CACHE_STORE=file
export DB_URL='' ASSET_URL=''
export APP_CONFIG_CACHE="$root/.tooling/demo-config.php"
if [[ -e "$APP_CONFIG_CACHE" || -L "$APP_CONFIG_CACHE" || -L "$DB_DATABASE" ]]; then
  printf '%s\n' 'Demonstração bloqueada: cache de configuração ou link simbólico no banco de teste.' >&2
  exit 1
fi
key="$root/.tooling/demo.key"
mkdir -p -- "$root/.tooling"
if [[ ! -s "$key" ]]; then
  openssl rand -base64 32 > "$key"
fi
export APP_KEY="base64:$(<"$key")"
"${php_cmd[@]}" artisan cidade:migrate-demo --no-interaction
if [[ "${1:-}" == --migrate-only ]]; then exit 0; fi
if [[ "${php_cmd[0]}" == php ]]; then
  exec php -S 127.0.0.1:8156 -t public
fi
exec "$root/.tooling/frankenphp-stable" run --config "$root/Caddyfile.demo" --adapter caddyfile
