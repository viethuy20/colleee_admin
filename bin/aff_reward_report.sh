#!/bin/sh
# cron www:fan
## admin.colleee.net entry execute
# 0 4 * * * $PHP_EXE $ADMIN_BASE_PATH/laravel/artisan aff_reward:report >/dev/null 2>&1
# 0 4 * * * $PHP_EXE $ADMIN_BASE_PATH/laravel/artisan aff_reward:action >/dev/null 2>&1

PHP_EXE="/usr/bin/env php"
BASE_PATH=$(cd $(dirname $0);cd ../;pwd)

$PHP_EXE $BASE_PATH/laravel/artisan aff_reward:report

$PHP_EXE $BASE_PATH/laravel/artisan aff_reward:action

exit 0
