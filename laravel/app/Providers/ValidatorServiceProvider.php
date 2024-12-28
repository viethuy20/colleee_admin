<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use \Validator;

class ValidatorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        Validator::extend('custom_email', function ($attribute, $value, $parameters, $validator) {
            /*
            * ドメインの存在確認する？
            */
            $check_dns = empty($parameters[0]) ? 0 : 1;

            switch (true) {
                /*
                * PHP7.1.0 よりも前では FILTER_FLAG_EMAIL_UNICODE が無いため
                * filter_var($email, FILTER_VALIDATE_EMAIL, FILTER_FLAG_EMAIL_UNICODE)
                * と書けないのです。
                */
                /*
                case false === filter_var($value, FILTER_VALIDATE_EMAIL):
                case !preg_match('/@(?!\[)(.+)\z/', $value, $m):
                    return false;
                */
                case !preg_match('/^([a-z0-9_]|\-|\.|\+)+@((([a-z0-9_]|\-)+\.)+[a-z]{2,6})$/', $value, $m):
                    return false;
                case !$check_dns:
                case checkdnsrr($m[2], 'MX'):
                case checkdnsrr($m[2], 'A'):
                case checkdnsrr($m[2], 'AAAA'):
                    return true;
                default:
                    return false;
            }
        });
        
        //
        Validator::extend('custom_alpha', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^[A-Za-z]+$/', $value);
        });
        //
        Validator::extend('custom_alpha_num', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^[A-Za-z\d]+$/', $value);
        });
        Validator::extend('custom_ipv4', function ($attribute, $value, $parameters, $validator) {
            $data = explode('/', $value);
            $long = ip2long($data[0]);
            // IPアドレス書式が不正の場合
            if ($long == -1 || $long === false) {
                return false;
            }
            // マスクがない場合
            if (!isset($data[1])) {
                return true;
            }
            // マスクが正常な値ではない場合
            if (!is_numeric($data[1]) || $data[1] < 0 || $data[1] > 32) {
                return false;
            }
            return true;
        });
        Validator::extend('secure_resource', function ($attribute, $value, $parameters) {
            // クライアント側がsslではない場合
            $app_url = config('app.client_url');
            if (!preg_match("/^https:\/\/.+$/", $app_url)) {
                return true;
            }
            return preg_match("/^https:\/\/.+$/", $value);
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
