FROM php:8.2-cli

RUN apt-get update && apt-get install -y unzip git && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /app

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

EXPOSE 8000

CMD ["/bin/bash", "docker-entrypoint.sh"]
