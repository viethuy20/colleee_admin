#!/bin/sh
# cron www:fan
## admin.colleee.net birthday execute
#1 9 * * * cd /home/www/admin.colleee.net/bin; sh birthday.sh >/dev/null 2>&1

PHP_EXE="/usr/bin/env php"
BASE_PATH=$(cd $(dirname $0);cd ../;pwd)

$PHP_EXE $BASE_PATH/laravel/artisan bonus:birthday

exit 0
