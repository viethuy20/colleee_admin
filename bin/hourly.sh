#!/bin/sh
# cron www:fan
## admin.colleee.net hourly execute
#0 * * * * cd /home/www/admin.colleee.net/bin; sh hourly.sh >/dev/null 2>&1

set -e

PHP_EXE="/usr/bin/env php"
BASE_PATH=$(cd $(dirname $0);cd ../;pwd)

$PHP_EXE $BASE_PATH/laravel/artisan program:app_driver

exit 0
