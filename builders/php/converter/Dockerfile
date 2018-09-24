FROM gcr.io/gcp-runtimes/ubuntu_18_0_4 AS builder

RUN apt-get update >/dev/null \
  && apt-get install -y golang >/dev/null

WORKDIR /tmp
COPY builders/php/converter/converter.go .
RUN go build converter.go

FROM gcr.io/gcp-runtimes/ubuntu_18_0_4

# Install PHP and Composer
RUN apt-get update >/dev/null \
  && apt-get install -y php7.2-cli php7.2-mbstring curl unzip git >/dev/null \
  && curl https://getcomposer.org/download/1.7.2/composer.phar > /usr/local/bin/composer \
  && chmod +x /usr/local/bin/composer

COPY invoker/ /invoker/
COPY --from=builder /tmp/converter /

WORKDIR /workspace

ENTRYPOINT ["/converter"]
