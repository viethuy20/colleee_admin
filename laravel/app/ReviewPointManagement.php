<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReviewPointManagement extends Model
{
    /*
     * ステータス
     * */
    const STATUS_END     = 1; // 終了済み
    const STATUS_START   = 2; // 公開中
    const STATUS_STANDBY = 3; // 公開待ち

    protected $table = 'review_point_managements';

    protected $fillable = ['point', 'start_at', 'stop_at'];

}
