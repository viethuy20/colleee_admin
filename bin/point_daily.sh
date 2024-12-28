#!/bin/sh
# cron www:fan
## admin.colleee.net daily execute
#* 3 * * * cd /home/www/admin.colleee.net/bin; sh point_daily.sh >/dev/null 2>&1

set -e

PHP_EXE="/usr/bin/env php"
BASE_PATH=$(cd $(dirname $0);cd ../;pwd)

$PHP_EXE $BASE_PATH/laravel/artisan point_month:report_daily

$PHP_EXE $BASE_PATH/laravel/artisan insert_friend_referral_bonus:entry

$PHP_EXE $BASE_PATH/laravel/artisan insert_friend_return_bonus:entry

exit 0