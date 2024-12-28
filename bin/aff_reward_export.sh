#!/bin/sh
# cron www:fan
# run at 23:59:00 daily in dev (for test)
## admin.colleee.net entry execute
# 59 23 * * *  cd /home/www/admin.colleee.net/bin; sh aff_reward_export.sh >> /var/log/aff_reward_export.log 2>&1
# 
# MONTH=0: current month
# MONTH=1: current month + 1
# MONTH=-1: current month -1
#
# ALL = --all: export all data from MONTH value to end
# ALL = "": exporrt data in MONTH

PHP_EXE="/usr/bin/env php"
BASE_PATH=$(cd $(dirname $0);cd ../;pwd)
MONTH=0 # current month
ALL="--all" # export all data

$PHP_EXE $BASE_PATH/laravel/artisan aff_reward:export-csv --month=$MONTH $ALL

exit 0
