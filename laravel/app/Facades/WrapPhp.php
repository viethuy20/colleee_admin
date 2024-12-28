<?php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;

 
class WrapPhp extends Facade {
    protected static function getFacadeAccessor() {
        return 'wrapphp';  // ①
    }
}