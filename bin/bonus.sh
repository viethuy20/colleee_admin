#!/bin/sh
# cron www:fan
## admin.colleee.net entry execute
#1 11 1 * * cd /home/www/admin.colleee.net/bin; sh bonus.sh >/dev/null 2>&1

PHP_EXE="/usr/bin/env php"
BASE_PATH=$(cd $(dirname $0);cd ../;pwd)

$PHP_EXE $BASE_PATH/laravel/artisan bonus:entry

$PHP_EXE $BASE_PATH/laravel/artisan bonus:program

exit 0
