PHP_EXE="/usr/bin/env php"
BASE_PATH=$(cd $(dirname $0);cd ../;pwd)

$PHP_EXE $BASE_PATH/laravel/artisan dot_money:deposit

exit 0
