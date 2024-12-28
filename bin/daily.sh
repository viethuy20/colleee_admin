#!/bin/sh
# cron www:fan
## admin.colleee.net daily execute
#0 9 * * * cd /home/www/admin.colleee.net/bin; sh daily.sh >/dev/null 2>&1

set -e

PHP_EXE="/usr/bin/env php"
BASE_PATH=$(cd $(dirname $0);cd ../;pwd)

$PHP_EXE $BASE_PATH/laravel/artisan partition:aff_reward

$PHP_EXE $BASE_PATH/laravel/artisan partition:pre_aff_reward

$PHP_EXE $BASE_PATH/laravel/artisan partition:exchange_request

$PHP_EXE $BASE_PATH/laravel/artisan partition:user_login

$PHP_EXE $BASE_PATH/laravel/artisan partition:email_token

$PHP_EXE $BASE_PATH/laravel/artisan partition:external_link

$PHP_EXE $BASE_PATH/laravel/artisan partition:ost_token

$PHP_EXE $BASE_PATH/laravel/artisan notice:friend

$PHP_EXE $BASE_PATH/laravel/artisan user:expire

$PHP_EXE $BASE_PATH/laravel/artisan user:refresh

$PHP_EXE $BASE_PATH/laravel/artisan cuenote:import

$PHP_EXE $BASE_PATH/laravel/artisan ntt_card:expire

$PHP_EXE $BASE_PATH/laravel/artisan paypay:check_reversal_details

exit 0
