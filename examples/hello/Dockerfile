FROM gcr.io/gae-runtimes/php73:php73_20191020_7_3_10_RC00

WORKDIR /srv/

# NOTE: The entrypoint "/start", which starts up NGINX and PHP-FPM,
# is configured by creating a `.googleconfig/app_start.json` file with the
# contents:
#
#     {"entrypointContents": "CUSTOM_ENTRYPOINT"}
#
# We configure it to use the `router.php` file included in this package.
RUN mkdir .googleconfig && \
    echo '{"entrypointContents": "serve vendor/bin/router.php"}' > .googleconfig/app_start.json

# Copy over composer files and run "composer install"
COPY composer.* ./
COPY --from=composer:1 /usr/bin/composer /usr/local/bin
RUN composer install --no-dev

# Copy over all application files
COPY . .

# Set a runtime name (required by the base image)
ENV GAE_RUNTIME php73
