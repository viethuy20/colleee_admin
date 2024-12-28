<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;

class Admin extends Authenticatable
{
    use Notifiable;
    
    /** 管理者権限. */
    const ADMIN_ROLE = 1;
    /** サポート権限. */
    const SUPPORT_ROLE = 2;
    /** 運用者権限. */
    const OPERATOR_ROLE = 3;
    /** 入稿権限. */
    const DRAFT_ROLE = 4;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'role',
    ];
    
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
    
     /**
     * 日付を変形する属性
     * @var array
     */
    protected $dates = ['deleted_at'];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    /**
     * 管理者情報初期値取得.
     * @return Admin 管理者情報
     */
    public static function getDefault()
    {
        $admin = new Admin();
        $admin->role = self::OPERATOR_ROLE;
        return $admin;
    }
}
