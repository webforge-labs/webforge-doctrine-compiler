#!/usr/bin/env sh
SRC_DIR="`pwd`"
cd "`dirname "$0"`"
PHP_TARGET="`pwd`/cli.php"
cd "$SRC_DIR"
php -f "$PHP_TARGET" "$@"