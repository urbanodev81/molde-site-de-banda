#!/usr/bin/env bash
set -e
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

case "$1" in
  artisan) COMANDO="php artisan ${*:2}" ;;
  pest)    COMANDO="./vendor/bin/pest ${*:2}" ;;
  pint)    COMANDO="./vendor/bin/pint ${*:2}" ;;
  *)       COMANDO="$*" ;;
esac

exec docker run --rm --entrypoint bash --network exemplo \
  -v "$DIR":/var/www/html -w /var/www/html \
  -u "$(id -u):$(id -g)" -e HOME=/tmp -e COMPOSER_HOME=/tmp/composer \
  sail-8.5/app -c "$COMANDO"
