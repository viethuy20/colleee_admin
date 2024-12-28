#!/bin/sh
# cron www:fan
## admin.colleee.net monthly execute
#0 2 2 * * cd /home/www/admin.colleee.net/bin; sh monthly.sh >/dev/null 2>&1

set -e

PHP_EXE="/usr/bin/env php"
BASE_PATH=$(cd $(dirname $0);cd ../;pwd)

$PHP_EXE $BASE_PATH/laravel/artisan partition:report
$PHP_EXE $BASE_PATH/laravel/artisan partition:history

$PHP_EXE $BASE_PATH/laravel/artisan user_point:expire

$PHP_EXE $BASE_PATH/laravel/artisan report:monthly
$PHP_EXE $BASE_PATH/laravel/artisan point_month:report
$PHP_EXE $BASE_PATH/laravel/artisan backup:user_provision

$PHP_EXE $BASE_PATH/laravel/artisan user_point:backup_delete
$PHP_EXE $BASE_PATH/laravel/artisan user:backup_delete
$PHP_EXE $BASE_PATH/laravel/artisan user_rank:backup_delete
$PHP_EXE $BASE_PATH/laravel/artisan bank_account:backup_delete
$PHP_EXE $BASE_PATH/laravel/artisan user_edit_log:backup_delete

exit 0
