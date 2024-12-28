<?php
namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * メールトークン.
 */
class EmailToken extends Model
{
    use DBTrait, PartitionTrait;
    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'email_tokens';

    protected $keyType = 'string';

    /**
     * createメソッド実行時に、入力を許可するカラムの指定
     * @var array
     */
    protected $fillable = ['id', 'expired_at', 'type', 'status', 'email'];

    /**
     * 日付を変形する属性
     * @var array
     */
    protected $dates = ['expired_at'];

    protected $casts = [
        'expired_at' => 'datetime',
    ];

    /**
     * 更新日時更新停止.
     * @var bool
     */
    public $timestamps = false;

    /**
     * 最適化実行.
     */
    public static function refreshPartition()
    {
        $instance = new static;

        $db_name = config('database.connections.mysql.database');
        //
        $tb_name = $instance->table;
        // パーティション有効期限
        $partition_expired = 31;
        // 予約パーティション数
        $reserved_partition = 10;

        return self::refreshDateRange($db_name, $tb_name, $partition_expired, $reserved_partition);
    }

    /** 作成種類. */
    const CREATE_TYPE = 1;
    /** 変更種類. */
    const EDIT_TYPE = 2;
    /** リマインダー種類. */
    const REMINDER_TYPE = 3;
    /** メールリマインダー種類. */
    const EMAIL_REMINDER_TYPE = 6;
    /**
     * メールトークン作成.
     * @param string $email メールアドレス
     * @param int $type 種類
     * @param stdObject|NULL $data データ
     * @return string 認可コード
     */
    public static function createToken(string $email, int $type, $data = null)
    {
        for ($i = 0; $i < 3; ++$i) {
            // メールトークンID作成
            $email_token_id = md5(uniqid(rand(), 1));
            // ロックキー作成
            $lock_key = sprintf("email_token_update_%s", $email_token_id);

            // 初期化
            $email_token = new EmailToken();
            $email_token->id = $email_token_id;
            $email_token->expired_at = Carbon::now()->addDays(3);
            $email_token->type = $type;
            $email_token->status = 0;
            $email_token->email = $email;
            if (isset($data)) {
                $email_token->data = json_encode($data);
            }

            $res = self::saveWithLock(
                $lock_key,
                function () use ($email_token) {
                    // 保存実行
                    $email_token->save();
                    return true;
                },
                function () use ($email_token_id) {
                    // メールトークンが重複した場合
                    return !EmailToken::where('id', '=', $email_token_id)->exists();
                }
            );

            // 保存に成功した場合
            if ($res) {
                // メールトークンID返却
                return $email_token_id;
            }
        }

        return null;
    }
}
