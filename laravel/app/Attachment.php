<?php
namespace App;

use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

use App\External\MountManager;

/**
 * 添付ファイル.
 */
class Attachment extends Model
{
    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'attachments';
    
    /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];
    //protected $fillable = ['url', 'status', 'deleted_at'];
    
    const IMG_DIR = 'img';
        
    /**
     * 絶対パスURL取得.
     * @return string URL
     */
    public function getFullUrlAttribute()
    {
        if (!isset($this->url)) {
            return null;
        }
        
        $file_url = $this->url;
        // 絶対パスのためそのまま返す
        if ((strpos($file_url, 'http') === 0)) {
            return $file_url;
        }
        // 相対パスなのでベースURLをつける
        return config('app.cms_app_url').$file_url;
    }
    
    public function scopeOfList($query, array $id_list)
    {
        return $query->whereIn($this->table.'.id', $id_list)
            ->orderBy($this->table.'.id', 'asc');
    }
    
    /**
     * 添付ファイル情報初期値取得.
     * @return Attachment 添付ファイル情報
     */
    public static function getDefault()
    {
        $attachment = new self();
        $attachment->status = 1;
        return $attachment;
    }
    
    /**
     * ファイル名取得.
     * @param string $ext 拡張子
     * @return string ファイル名
     */
    private function createFileName(string $ext) :string
    {
        return sprintf(
            "%d%03d%03d.%s",
            Carbon::now()->format('YmdHis'),
            substr(explode(".", (microtime(true) . ""))[1], 0, 3),
            mt_rand(0, 999),
            $ext
        );
    }
    
    /**
     * 添付ファイル情報を保存.
     * @param UploadedFile $file アップロードファイル
     * @param int $status ステータス
     */
    public function saveImg(UploadedFile $file, int $status = 0)
    {
        $attachment = $this;
        
        for ($i = 0; $i < 3; ++$i) {
            // ファイル名作成
            $file_name = self::IMG_DIR.'/'.$this->createFileName($file->getClientOriginalExtension());
            // ファイルが存在しない場合
            if (!MountManager::fileExists(MountManager::IMG_TYPE, $file_name)) {
                break;
            }
            $file_name = null;
        }
        
        // ファイル名が取得できなかった場合
        if (!isset($file_name)) {
            return false;
        }
        
        // ファイルをアップロード
        MountManager::upload(MountManager::IMG_TYPE, $file_name, $file);
        // URL作成
        $attachment->url = '/'.$file_name;

        // ステータス
        $attachment->status = $status;
        // 保存実行
        return DB::transaction(function () use ($attachment) {
            $attachment->save();
            return true;
        });
    }
    
    /**
     * ファイルのステータスを有効にする.
     * @param array $attachment_id_list 添付ファイルIDリスト
     * @return bool 成功の場合はtrueを、失敗の場合はfalseを返すs
     */
    public static function enable(array $attachment_id_list) :bool
    {
        // 保存実行
        return DB::transaction(function () use ($attachment_id_list) {
            Attachment::whereIn('id', $attachment_id_list)
                ->where('status', '=', 1)
                ->update(['status' => 0]);
            return true;
        });
    }
    
    /**
     * ファイルのステータスを無効にする.
     * @param array $attachment_id_list 添付ファイルIDリスト
     * @return bool 成功の場合はtrueを、失敗の場合はfalseを返すs
     */
    public static function disable(array $attachment_id_list) :bool
    {
        // 保存実行
        return DB::transaction(function () use ($attachment_id_list) {
            Attachment::whereIn('id', $attachment_id_list)
                ->where('status', '=', 0)
                ->update(['status' => 1, 'deleted_at' => Carbon::now()]);
            return true;
        });
    }
}
