#!/bin/sh
# cron www:fan
## admin.colleee.net 1 hour execute
#0 * * * * cd /home/www/admin.colleee.net/bin; sh exchange_alert.sh >/dev/null 2>&1

PHP_EXE="/usr/bin/env php"
BASE_PATH=$(cd $(dirname $0);cd ../;pwd)

$PHP_EXE $BASE_PATH/laravel/artisan exchange:alert

exit 0