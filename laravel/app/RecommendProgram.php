<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class RecommendProgram extends Model
{
    protected $table = 'recommend_programs';
    protected $guarded = ['id'];
   // protected $dates = ['start_at', 'stop_at', 'deleted_at'];

   const STATUS_END     = 1; // 終了済み
   const STATUS_START   = 2; // 公開中
   const STATUS_STANDBY = 3; // 公開待ち

   public static function getDefault()
   {
       $recommend_program = new self();
       return $recommend_program;
   }

   public function saveRecommendProgram()
   {
        $is_dirty = $this->isDirty();
        
        if ($is_dirty) {
            // 登録実行
            return $this->save();
        }
       return true;
   }

    
}