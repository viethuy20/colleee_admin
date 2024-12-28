#!/bin/sh
# cron www:fan
## admin.colleee.net 30 min execute
#*/30 * * * * /home/www/admin.colleee.net/bin; sh down_up_stock_program.sh >/dev/null 2>&1

set -e

PHP_EXE="/usr/bin/env php"
BASE_PATH=$(cd $(dirname $0);cd ../;pwd)

$PHP_EXE $BASE_PATH/laravel/artisan program:up_down_stock

exit 0