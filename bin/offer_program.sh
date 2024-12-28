#!/bin/sh
# cron www:fan
## admin.colleee.net entry execute
#* */1 * * * cd /home/www/admin.colleee.net/bin; sh offer_program.sh >/dev/null 2>&1

PHP_EXE="/usr/bin/env php"
BASE_PATH=$(cd $(dirname $0);cd ../;pwd)

$PHP_EXE $BASE_PATH/laravel/artisan program:gree_ads_rewards_offer

$PHP_EXE $BASE_PATH/laravel/artisan program:sky_flag_offer --platform_type=1

$PHP_EXE $BASE_PATH/laravel/artisan program:sky_flag_offer --platform_type=2

exit 0
