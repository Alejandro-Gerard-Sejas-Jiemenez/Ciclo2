FROM php:8.2-fpm

# Evitar prompts interactivos
ENV DEBIAN_FRONTEND=noninteractive

# Instalar dependencias del sistema incluyendo Node.js 20
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    ca-certificates \
    gnupg \
    lsb-release \
    libpq-dev \
 && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
 && apt-get install -y nodejs \
 && npm install -g npm@10.8.2 \
 && docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd zip \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Establecer el directorio de trabajo
WORKDIR /app

# Copiar los archivos de composer para instalar dependencias PHP
COPY composer.json composer.lock ./

RUN composer install --no-interaction --optimize-autoloader --no-scripts

# Copiar el resto del código fuente
COPY . .

# Ajustar permisos necesarios para Laravel
RUN chown -R www-data:www-data /app \
 && chmod -R 755 /app/storage /app/bootstrap/cache /app/public

# Descargar wait-for-it.sh
RUN curl -o /usr/local/bin/wait-for-it.sh https://raw.githubusercontent.com/vishnubob/wait-for-it/master/wait-for-it.sh \
 && chmod +x /usr/local/bin/wait-for-it.sh

EXPOSE 9000

# Comando de inicio del contenedor
CMD sh -c "chown -R www-data:www-data /app/storage /app/bootstrap/cache && chmod -R 775 /app/storage /app/bootstrap/cache && wait-for-it.sh postgres:5432 -- php artisan migrate --force && php artisan db:seed && php-fpm"
