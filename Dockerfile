# Imagem para o Vercel (preset "Container"): Laravel servido pelo FrankenPHP.
# O Vercel detecta este Dockerfile na raiz do repositório.
FROM dunglas/frankenphp:1-php8.4-bookworm

RUN install-php-extensions pdo_pgsql intl zip opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Dependências primeiro, para aproveitar o cache de camadas.
COPY app/composer.json app/composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

COPY app/ ./
RUN composer dump-autoload --no-dev --classmap-authoritative --no-interaction \
 && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
 && php artisan view:cache
RUN setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp \
 && chown -R www-data:www-data storage bootstrap/cache /data/caddy /config/caddy

COPY Caddyfile.vercel /etc/caddy/Caddyfile

# Padrões seguros para produção. Segredos (APP_KEY, DB_URL, SECRETARIA_*) vêm
# das variáveis de ambiente do projeto no Vercel, nunca da imagem.
ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=pgsql \
    SESSION_DRIVER=cookie \
    SESSION_SECURE_COOKIE=true \
    CACHE_STORE=array \
    QUEUE_CONNECTION=sync \
    CIDADE_DEMO=false \
    PORT=80

USER www-data

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
