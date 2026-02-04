FROM mcr.microsoft.com/devcontainers/php:1-8.3

USER root

RUN mkdir -p /usr/local/etc/php/conf.d

# Yarn APT repo ist im Base-Image drin, aber Key fehlt -> apt-get update knallt.
# Wir brauchen Yarn nicht (Node kommt als Feature), also Repo einfach deaktivieren.
RUN rm -f /etc/apt/sources.list.d/yarn.list /etc/apt/sources.list.d/yarn.sources || true

RUN apt-get update \
 && apt-get install -y --no-install-recommends libgmp-dev \
 && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install bcmath gmp

USER vscode
