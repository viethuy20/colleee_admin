#!/bin/sh
# cron www:fan
## admin.colleee.net 5 min execute
#*/5 * * * * cd /home/www/admin.colleee.net/bin; sh five_min.sh >/dev/null 2>&1

PHP_EXE="/usr/bin/env php"
BASE_PATH=$(cd $(dirname $0);cd ../;pwd)

$PHP_EXE $BASE_PATH/laravel/artisan payment_gateway:transfer

$PHP_EXE $BASE_PATH/laravel/artisan voyage:issu

$PHP_EXE $BASE_PATH/laravel/artisan ntt_card:issu

$PHP_EXE $BASE_PATH/laravel/artisan dot_money:deposit

$PHP_EXE $BASE_PATH/laravel/artisan d_point:grant

$PHP_EXE $BASE_PATH/laravel/artisan line_pay:deposit

$PHP_EXE $BASE_PATH/laravel/artisan paypay:give_cashback

$PHP_EXE $BASE_PATH/laravel/artisan paypay:check_cashback

$PHP_EXE $BASE_PATH/laravel/artisan kdol:give_cashback

$PHP_EXE $BASE_PATH/laravel/artisan kdol:check_cashback


exit 0
