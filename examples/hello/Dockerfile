FROM gcr.io/gae-runtimes/php73:php73_20190827_7_3_7_RC03

WORKDIR /srv/

# NOTE: The entrypoint "/start", which starts up NGINX and PHP-FPM,
# is configured by creating a `.googleconfig/app_start.json` file with the
# contents:
#     {"entrypointContents": "serve vendor/bin/router.php"}
RUN mkdir .googleconfig && \
    echo '{"entrypointContents": "serve vendor/bin/router.php"}' > .googleconfig/app_start.json

# Copy over all application files
COPY composer.* ./

# Run "composer install"
COPY --from=composer:1 /usr/bin/composer /usr/local/bin
RUN composer install --no-dev

COPY . .

# Set a runtime name and the function to use
ENV GAE_RUNTIME php73
