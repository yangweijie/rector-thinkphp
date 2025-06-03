# ThinkPHP Rector Docker Image
FROM php:8.2-cli-alpine

# Set working directory
WORKDIR /app

# Install system dependencies
RUN apk add --no-cache \
    git \
    unzip \
    curl \
    bash \
    mysql-client \
    nodejs \
    npm \
    && rm -rf /var/cache/apk/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mysqli \
    pcntl \
    sockets

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create non-root user
RUN addgroup -g 1000 rector && \
    adduser -u 1000 -G rector -s /bin/bash -D rector

# Copy application files
COPY . /app/

# Set permissions
RUN chown -R rector:rector /app

# Switch to non-root user
USER rector

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Install Node.js dependencies for web interface
RUN npm install --production

# Create necessary directories
RUN mkdir -p /app/storage/logs \
    /app/storage/cache \
    /app/storage/reports \
    /app/storage/backups

# Expose ports
EXPOSE 8080 8081

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8080/api/health || exit 1

# Default command
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
