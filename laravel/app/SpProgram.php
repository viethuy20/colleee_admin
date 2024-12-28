<?php
namespace App;

use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Model;

/**
 * 特別プログラム.
 */
class SpProgram extends Model
{
    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'sp_programs';
    
     /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];
    
    /**
     * 日付を変形する属性
     * @var array
     */
    protected $dates = ['start_at', 'stop_at'];
    
    protected $casts = [
        'start_at' => 'datetime',
        'stop_at' => 'datetime',
    ];

    // @codingStandardsIgnoreStart
    public function sp_program_type()
    {
        // @codingStandardsIgnoreEnd
        return $this->belongsTo(SpProgramType::class, 'sp_program_type_id', 'id');
    }
    
    /**
     * 特別プログラム情報初期値取得.
     * @return SpProgram 特別プログラム情報
     */
    public static function getDefault(SpProgramType $sp_program_type) : SpProgram
    {
        $sp_program = new self();
        $sp_program->sp_program_type_id = $sp_program_type->id;
        $sp_program->category_id = $sp_program_type->category_id;
        $sp_program->devices = $sp_program_type->default_devices;
        $sp_program->point = 0;
        $sp_program->status = 2;
        $now = Carbon::now();
        $sp_program->start_at = Carbon::create($now->year, $now->month, $now->day, $now->hour, $now->minute, 0);
        $sp_program->stop_at = $sp_program->start_at->copy()->addYears(40);
        return $sp_program;
    }
    
    /**
     * 特別プログラム情報を保存.
     * @return bool 成功の場合はtrueを、失敗の場合はfalseを返す
     */
    public function saveSpProgram()
    {
        // トランザクション処理
        $sp_program = $this;
        return DB::transaction(function () use ($sp_program) {
            // 登録実行
            $sp_program->save();
            return true;
        });
    }
}
