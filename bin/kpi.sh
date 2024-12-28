#!/bin/sh
# cron www:fan
## admin.colleee.net entry execute
#0 0 * * * $PHP_EXE $ADMIN_BASE_PATH/laravel/artisan save:kpi >/dev/null 2>&1

PHP_EXE="/usr/bin/env php"
BASE_PATH=$(cd $(dirname $0);cd ../;pwd)

$PHP_EXE $BASE_PATH/laravel/artisan save:kpi

exit 0
