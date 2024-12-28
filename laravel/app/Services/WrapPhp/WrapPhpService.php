<?php
namespace App\Services\WrapPhp;

class WrapPhpService
{
    public function count($count=null)
    {
        if(!empty($count) && is_countable($count)) {
            return count($count);
        }else{
            return 0;
        }
    }

}