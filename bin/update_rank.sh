#!/bin/sh
# cron www:fan
## admin.colleee.net update rank execute
#1 0 1 * * cd /home/www/admin.colleee.net/bin; sh update_rank.sh >/dev/null 2>&1

PHP_EXE="/usr/bin/env php"
BASE_PATH=$(cd $(dirname $0);cd ../;pwd)

$PHP_EXE $BASE_PATH/laravel/artisan user_rank:update

exit 0
