FROM php:8.3-cli-alpine

# Install required PHP extensions and dependencies
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS libxml2-dev \
    && docker-php-ext-install -j$(nproc) dom bcmath \
    && apk del .build-deps \
    && apk add --no-cache libxml2

# Set working directory
WORKDIR /app

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy only necessary files
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-dev --optimize-autoloader \
    && composer clear-cache

# Copy application files
COPY bin/ ./bin/
COPY src/ ./src/

# Make the original script executable
RUN chmod +x /app/bin/ob.sh

# Create a simpler entrypoint script
RUN echo '#!/bin/sh' > /entrypoint.sh && \
    echo 'if [ -f /workdir/.env ]; then' >> /entrypoint.sh && \
    echo '  echo "Found .env file in current directory, using it..."' >> /entrypoint.sh && \
    echo '  ln -sf /workdir/.env /app/.env' >> /entrypoint.sh && \
    echo 'else' >> /entrypoint.sh && \
    echo '  echo "Warning: No .env file found in current directory."' >> /entrypoint.sh && \
    echo 'fi' >> /entrypoint.sh && \
    echo 'exec /app/bin/ob.sh "$@"' >> /entrypoint.sh && \
    chmod +x /entrypoint.sh && \
    # Clean up to reduce image size\n\
    rm -rf /tmp/* && \
    rm -rf /var/cache/apk/*

# Use the entrypoint script
ENTRYPOINT ["/entrypoint.sh"]