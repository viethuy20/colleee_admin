#!/bin/sh
# cron www:fan
## admin.colleee.net entry execute

PHP_EXE="/usr/bin/env php"
BASE_PATH=$(cd $(dirname $0);cd ../;pwd)

$PHP_EXE $BASE_PATH/laravel/artisan insert_friend_point:entry

exit 0
