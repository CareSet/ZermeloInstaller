#!/bin/sh
if ! [ -x "$(command -v composer)" ]; then
  echo 'Error: composer is not installed. try brew install composer' >&2
  exit 1
fi
cp .env.example .env
composer update
php artisan key:generate
