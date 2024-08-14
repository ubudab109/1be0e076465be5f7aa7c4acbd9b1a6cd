# Use PHP 8.2.12 as the base image
FROM php:8.2.12-fpm

# Install system dependencies and PHP extensions required for Laravel
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && pecl install redis \
    && docker-php-ext-enable redis

# Set the working directory in the container
WORKDIR /var/www/html

# Copy existing application directory contents
COPY . .

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install application dependencies
RUN composer install

# Copy the custom entrypoint script into the container
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Expose port 8000 for the PHP built-in server
EXPOSE 8000

# Use the custom entrypoint script to start the PHP server and worker
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
