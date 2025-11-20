# Image de base (PHP 8.2 + FPM)
FROM php:8.2-fpm
RUN docker-php-ext-install pdo_mysql
WORKDIR /var/www/html


# Mise à jour et installation des dépendances système
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    libpq-dev \
    acl \
    && rm -rf /var/lib/apt/lists/*

# Extensions PHP essentielles pour Symfony
RUN docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    opcache \
    intl

# Configuration OPcache (optimisation performances)
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini

# Installer Composer globalement
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Créer un utilisateur spécifique (meilleure pratique sécurité)
RUN groupadd -g 1000 appuser \
    && useradd -u 1000 -g appuser -m -d /home/appuser appuser \
    && mkdir -p /var/www/html/var \
    && chown -R appuser:appuser /var/www/html

# Définir l'utilisateur et le répertoire de travail
USER appuser
WORKDIR /var/www/html

# Script de démarrage personnalisé (optionnel)
COPY --chmod=+x docker/php/startup.sh /usr/local/bin/startup  
ENTRYPOINT ["startup"]
CMD ["php-fpm"]

RUN apk add --no-cache \
libjpeg-turbo-dev \
libwebp-dev \
libpng-dev \
freetype-dev \
zip \
libzip-dev \
icu-dev \
oniguruma-dev

RUN docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
&& docker-php-ext-install -j$(nproc) \
pdo_mysql \
mbstring \
exif \
pcntl \
bcmath \
gd \
zip \
opcache \
intl