#!/bin/sh
# cron www:fan
## admin.colleee.net 5 min execute
#*/10 * * * * cd /home/www/admin.colleee.net/bin; sh ten_min.sh >/dev/null 2>&1

PHP_EXE="/usr/bin/env php"
BASE_PATH=$(cd $(dirname $0);cd ../;pwd)

$PHP_EXE $BASE_PATH/laravel/artisan paypay:check_retry_cashback

exit 0