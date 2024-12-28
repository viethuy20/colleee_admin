#!/bin/sh
# cron www:fan
## admin.colleee.net rakuten confirm
#0 12 * * * cd /home/www/admin.colleee.net/bin; sh rakuten_confirm.sh >/dev/null 2>&1

PHP_EXE="/usr/bin/env php"
BASE_PATH=$(cd $(dirname $0);cd ../;pwd)

$PHP_EXE $BASE_PATH/laravel/artisan rakuten_bank:confirm

exit 0
