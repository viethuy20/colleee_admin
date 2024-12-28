<?php
namespace App;

use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Model;
use DateTime;
use App\External\IGiftCode;
use App\External\NttCard;
use App\External\Voyage;


use Illuminate\Support\Facades\Log;

/**
 * 交換申し込み.
 */
class ExchangeRequest extends Model
{
    use DBTrait, PartitionTrait;

    /** 金融機関. */
    const BANK_TYPE = 1;
    /** Edyギフトコード. */
    const EDY_GIFT_TYPE = 9;
    /** アマゾンギフトコード. */
    const AMAZON_GIFT_TYPE = 10;
    /** iTunesギフトコード. */
    const ITUNES_GIFT_TYPE = 11;
    /** PEXポイントギフトコード. */
    const PEX_GIFT_TYPE = 5;
    /** NANACOギフトコード. */
    const NANACO_GIFT_TYPE = 12;
    /** .moneyポイント. */
    const DOT_MONEY_POINT_TYPE = 8;
    /** GooglePlayギフトコード. */
    const GOOGLE_PLAY_GIFT_TYPE = 13;
    /** WAONギフトコード. */
    const WAON_GIFT_TYPE = 14;
    /** Dポイント. */
    const D_POINT_TYPE = 15;
    /** LINE PAY. */
    const LINE_PAY_TYPE = 16;
    /** Pontaポイントギフトコード. */
    const PONTA_GIFT_TYPE = 17;
    /** プレイステーション ストアチケット. */
    const PSSTICKET_GIFT_TYPE = 18;
    /** PAYPAY */
    const PAYPAY_TYPE = 19;
    /** KDOL */
    const KDOL_TYPE = 22;

    /** デジタルギフト PayPal*/
    const DIGITAL_GIFT_PAYPAL_TYPE = 20;


    /** デジタルギフトJALMile */
    const DIGITAL_GIFT_JAL_MILE_TYPE = 21;

    /** 正常. */
    const SUCCESS_STATUS = 0;
    /** 組み戻し. */
    const ROLLBACK_STATUS = 1;
    /** 申込中. */
    const WAITING_STATUS = 2;
    /** エラー. */
    const ERROR_STATUS = 3;
    /** 停止. */
    const STOP_STATUS = 4;
    /** 交換申請中 */
    const EXCHANGE_WAITING_STATUS = 5;

    /** PayPay交換申請中 */
    const PAYPAY_WAITING_STATUS = 5;
    /** PayPayリトライ対象 */
    const PAYPAY_RETRY_STATUS = 6;

    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'exchange_requests';
    /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * 日付を変形する属性
     * @var array
     */
    protected $dates = ['scheduled_at', 'requested_at', 'confirmed_at'];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'requested_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    private static $VOYAGE_GIFT_CODE_LIST = [self::PEX_GIFT_TYPE,];

    private static $NTT_GIFT_CODE_LIST = [
        self::AMAZON_GIFT_TYPE,
        self::ITUNES_GIFT_TYPE,
        self::EDY_GIFT_TYPE,
        self::NANACO_GIFT_TYPE,
        self::GOOGLE_PLAY_GIFT_TYPE,
        self::WAON_GIFT_TYPE,
        self::PONTA_GIFT_TYPE,
        self::PSSTICKET_GIFT_TYPE,
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function exchange_request_cashback_key()
    {
        return $this->hasOne('App\ExchangeRequestCashbackKey');
    }

    /**
     * ユーザー名を取得.
     * @return string ユーザー名
     */
    public function getUserNameAttribute()
    {
        return User::getNameById($this->user_id);
    }

    /**
     * 銀行口座情報を取得.
     * @return BankAccount|NULL 銀行口座情報
     */
    public function getBankAccountAttribute()
    {
        // 金融機関振込申し込み以外の場合
        if ($this->type != self::BANK_TYPE) {
            return null;
        }
        return BankAccount::find($this->account_id);
    }

    private function getConfig()
    {
        return config('exchange.point.'.$this->type);
    }

    private function getInnerConfig()
    {
        $config = $this->getConfig();
        $key = $config['config'];
        return isset($key) ? config($key) : null;
    }

    /**
     * ユーザーポイントを取得.
     * @return UserPoint ユーザーポイント
     */
    public function getUserPointAttribute() : UserPoint
    {
        $config = $this->getInnerConfig();
        return UserPoint::where('type', '=', $config['type'])
            ->where('parent_id', '=', $this->id)
            ->first();
    }

    /**
     * 申し込み番号からID取得.
     * @param string $number 申し込み番号
     * @return int ID
     */
    public static function getIdByNumber(string $number) : int
    {
        return intval(substr($number, 3));
    }

    /**
     * 申し込み番号取得.
     * @return string 申し込み番号
     */
    public function getNumberAttribute() : string
    {
        switch (config('app.env')) {
            case 'local':
                $p = 'L';
                break;
            case 'development':
                $p = 'D';
                break;
            default:
                $p = 'C';
                break;
        }
        $config = $this->getInnerConfig();
        return $p.$config['prefix'].sprintf("%017d", $this->id);
    }

    /**
     * ステータスメッセージ取得.
     * @return string メッセージ
     */
    public function getStatusMessageAttribute()
    {
        $config = $this->getInnerConfig();
        return $config['status'][$this->status];
    }
    /**
     * 応答メッセージ取得.
     * @return string 結果メッセージ
     */
    public function getResMessageAttribute()
    {
        // ステータスと応答コードを確認 条件変更
        //if ($this->status == self::SUCCESS_STATUS || $this->status == self::STOP_STATUS || !isset($this->response_code)) {
        if (!isset($this->response_code)) {

            return null;
        }

        $config = $this->getInnerConfig();

        $response_messages = [];
        // PaymentGateWayの場合複数エラーケースに対応
        foreach(explode(',', $this->response_code) as $response_code) {
            $response_messages[] = $config['response_code'][$response_code] ?? '未確認エラー';
        }

        return implode(",", $response_messages);
    }

    /**
     * 額面取得.
     * @return int 額面
     */
    public function getFaceValueAttribute() : int
    {
        $config = $this->getConfig();
        return $this->yen * $config['yen_rate'] / 100;
    }

    /**
     * 額面ラベル取得.
     * @return string 額面
     */
    public function getFaceValueLabelAttribute() : string
    {
        $config = $this->getConfig();
        return number_format($this->face_value).$config['unit'];
    }

    /**
     * ギフトコード種類取得.
     * @return mixed ギフトコード種類
     */
    public function getGiftTypeAttribute()
    {
        $config = $this->getConfig();
        return $config['gift_type'] ?? 0;
    }

    /**
     * ギフトコード確認.
     * @return bool ギフトコードの場合はtrueを、そうでない場合はfalseを返す
     */
    public function getIsGiftCodeAttribute() : bool
    {
        return in_array($this->type, self::$VOYAGE_GIFT_CODE_LIST, true) ||
            in_array($this->type, self::$NTT_GIFT_CODE_LIST, true);
    }

    /**
     * ギフトコード情報取得.
     * @return IGiftCode|null ギフトコード情報
     */
    public function getGiftCodeAttribute() : ?IGiftCode
    {
        $key = 'gift_code';

        if (array_key_exists($key, $this->appends)) {
            return $this->appends[$key];
        }
        $gift_code = null;
        // ギフトコードではない、またはレスポンスが存在しない場合
        if ($this->is_gift_code && isset($this->response)) {
            $config = $this->getInnerConfig();
            $gift_code = ($config['class'])::parse($this->response);
        }
        $this->appends[$key] = $gift_code;
        return $gift_code;
    }

    public function scopeOfBank($query)
    {
        return $query->where('type', '=', self::BANK_TYPE);
    }

    public function scopeOfGiftCode($query)
    {
        $gift_list = array_merge(self::$VOYAGE_GIFT_CODE_LIST, self::$NTT_GIFT_CODE_LIST);
        return $query->whereIn('type', $gift_list);
    }

    public function scopeOfVoyageGiftCode($query)
    {
        return $query->whereIn('type', self::$VOYAGE_GIFT_CODE_LIST);
    }

    public function scopeOfNttCardGiftCode($query)
    {
        return $query->whereIn('type', self::$NTT_GIFT_CODE_LIST);
    }

    public function scopeOfDotMoney($query)
    {
        return $query->where('type', '=', self::DOT_MONEY_POINT_TYPE);
    }

    public function scopeOfDPoint($query)
    {
        return $query->where('type', '=', self::D_POINT_TYPE);
    }

    public function scopeOfLinePay($query)
    {
        return $query->where('type', '=', self::LINE_PAY_TYPE);
    }

    public function scopeOfDigitalGiftPaypal($query)
    {
        return $query->where('type', '=', self::DIGITAL_GIFT_PAYPAL_TYPE);
    }

    public function scopeOfPayPay($query)
    {
        return $query->where('type', '=', self::PAYPAY_TYPE);
    }

    public function scopeOfKdol($query)
    {
        return $query->where('type', '=', self::KDOL_TYPE);
    }

    public function scopeOfPaypayWaiting($query)
    {
        return $query->where('status', '=', self::PAYPAY_WAITING_STATUS);
    }

    public function scopeOfPaypayRetry($query)
    {
        return $query->where('status', '=', self::PAYPAY_RETRY_STATUS);
    }



    public function scopeOfDigitalGiftJalMile($query)
    {
        return $query->where('type', '=', self::DIGITAL_GIFT_JAL_MILE_TYPE);
    }

    public function scopeOfWaiting($query, $last_exchange_request_id = 0)
    {
        return $query->where('status', '=', self::WAITING_STATUS)
            ->where('id', '>', $last_exchange_request_id)
            ->orderBy('id', 'asc');
    }

    public function scopeOfRollback($query)
    {
        return $query->where('status', '=', self::ROLLBACK_STATUS);
    }

    public function scopeOfExchangeWaiting($query)//交換申請中
    {
        return $query->where('status', '=', self::EXCHANGE_WAITING_STATUS);
    }

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
        $partition_expired = 365;
        // 予約パーティション数
        $reserved_partition = 7;

        return self::refreshDateRange(
            $db_name,
            $tb_name,
            $partition_expired,
            $reserved_partition
        );
    }

    /**
     * ギフトコードを送信.
     * @return bool 成功の場合はtrueを、失敗の場合はfalseを返す
     */
    public function sendGiftCode() : bool
    {
        $user = $this->user;
        $config = $this->getConfig();

        // メール送信を実行
        try {
            $mailable = new \App\Mail\GiftCode(
                $user->email,
                $config['email'],
                $this->number,
                ['user' => $user, 'gift_data' => $this->gift_code]
            );
            \Mail::send($mailable);
        } catch (\Exception $e) {
            //throw $e;
            return false;
        }
        return true;
    }

    /**
     * 承認.
     * @return bool 成功の場合はtrueを、失敗の場合はfalseを返す
     */
    public function approvalRequest() : bool
    {
        $this->status = self::SUCCESS_STATUS;
        $exchange_request = $this;

        return DB::transaction(function () use ($exchange_request) {
            $exchange_request->save();
            return true;
        });
    }

    /**
     * 組戻し.
     * @param int $admin_id 更新管理者ID
     */
    public function rollbackRequest(int $admin_id = 0) : bool
    {
        $this->status = self::ROLLBACK_STATUS;
        $exchange_request = $this;
        $user_point = $this->user_point;

        return $user_point->rollbackPoint(
            $admin_id,
            '組戻し',
            function () use ($exchange_request) {
                // チケットも戻す
                if ($exchange_request->use_ticket == 1) {
                    User::where('id', '=', $exchange_request->user_id)
                        ->update(['ticketed_at' =>
                            Carbon::now()->startOfMonth()->addSeconds(-1)]);
                }

                // 実行
                $exchange_request->save();
                return true;
            }
        );
    }

    /**
     * ユーザーを確認して組み戻しを実行.
     * @return bool 正規のユーザーではないため組み戻された場合はtrueを、そうでない場合はfalseを返す
     */
    public function checkRollbackUser() : bool
    {
        $user = $this->user;
        // ユーザーが存在しない、または交換不可能の場合
        if (!isset($user->id) || !$user->enable_exchange) {
            // 自動で組戻し
            $this->rollbackRequest();
            return true;
        }
        return false;
    }

    /**
     * PayPay交換申請中にstatus変更
     * @return bool 成功の場合はtrueを、失敗の場合はfalseを返す
     */
    public function paypayGiveCashbackRequest() : bool
    {
        if($this->status != self::WAITING_STATUS){
            return false;
        }
        $this->status = self::PAYPAY_WAITING_STATUS;
        $exchange_request = $this;

        return DB::transaction(function () use ($exchange_request) {
            $exchange_request->save();
            return true;
        });
    }

    /**
     * PayPayリトライ対象にstatus変更
     * @return bool 成功の場合はtrueを、失敗の場合はfalseを返す
     */
    public function paypayRetryRequest() : bool
    {
        if(!($this->status == self::WAITING_STATUS || $this->status == self::PAYPAY_WAITING_STATUS)){
            return false;
        }
        $this->status = self::PAYPAY_RETRY_STATUS;
        $exchange_request = $this;

        return DB::transaction(function () use ($exchange_request) {
            $exchange_request->save();
            return true;
        });
    }


    public function getExchangeInfoAttribute()
    {
        return ExchangeInfo::ofType($this->type)
            ->ofTerm($this->created_at)
            ->first();
    }

    /**
     * デジタルギフトのURLを送信.
     * @return bool 成功の場合はtrueを、失敗の場合はfalseを返す
     */
    public function sendDigitalGiftUrl($url,$code_name=''): bool
    {
        $user = $this->user;
        $config = $this->getConfig();

        // メール送信を実行
        try {
            $mailable = new \App\Mail\DigitalGiftUrl(
                $user->email,
                $config['email'],
                $this->number,
                ['user' => $user, 'url' => $url, 'code_name' => $code_name]
            );
            \Mail::send($mailable);
        } catch (\Exception $e) {
            //throw $e;
            return false;
        }
        return true;
    }

    public function getDigitalGiftResponseRequestAttribute()
    {
        $exchange_request = $this;
        if($exchange_request->response){
            $response = json_decode($exchange_request->response);
            return isset($response->body)?$response->body:'';
        }
        return false;
    }

    public function getDigitalGiftUrlAttribute()
    {
        $request = $this->digital_gift_response_request;
        if($request){
            return isset($request->gift)?$request->gift->url:'';
        }
        return '';
    }

    public function getDigitalGiftCodeAttribute()
    {
        $request = $this->digital_gift_response_request;
        if($request){
            return isset($request->gift)?$request->gift->code:'';
        }
        return '';
    }

    public function getDigitalGiftExpireAtAttribute()
    {
        $request = $this->digital_gift_response_request;
        if($request && isset($request->gift)){
            $datetime = DateTime::createFromFormat('Y-m-d\TH:i:s+', $request->gift->expire_at);
        return $datetime->format("Y-m-d H:i:s");
        }
        return '';

    }
}
