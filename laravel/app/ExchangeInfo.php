<?php
namespace App;

use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Model;

/**
 * 交換情報.
 */
class ExchangeInfo extends Model
{
    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'exchange_infos';
    
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

    /** 正常. */
    const SUCCESS_STATUS = 0;
    /** 公開停止. */
    const STOP_STATUS = 1;

    public function scopeOfType($query, int $type)
    {
        return $query->where('type', '=', $type);
    }

    public function scopeOfTerm($query, Carbon $created_at)
    {
        return $query->where('stop_at', '>=', $created_at)
            ->where('start_at', '<=', $created_at);
    }

    /**
     * 開始確認.
     * @return bool trueの場合は開始、falseの場合はまだ開始していない
     */
    public function getStartedAttribute()
    {
        $now = Carbon::now();
        return isset($this->id) && $this->start_at->lt($now);
    }

    /**
     * 停止確認.
     * @return bool trueの場合は停止、falseの場合はそれ以外
     */
    public function getStoppedAttribute()
    {
        $now = Carbon::now();
        return isset($this->id) && $this->stop_at->lt($now);
    }

    public function getPreviousAttribute() :?ExchangeInfo
    {
        $query = self::ofType($this->type)
            ->orderBy('id', 'desc');
        if (isset($this->id)) {
            $query = $query->where('id', '<', $this->id);
        }
        return $query->first();
    }

    public function getNextAttribute() :?ExchangeInfo
    {
        if (!isset($this->id)) {
            return null;
        }
        return self::ofType($this->type)
            ->where('id', '>', $this->id)
            ->orderBy('id', 'asc')
            ->first();
    }

    public function getStartAtMinAttribute() : Carbon
    {
        $now = Carbon::now();
        // すでに保存済みで開始している場合、変更できないのでそのままの日時を渡す
        if (isset($this->id) && $this->start_at->lt($now)) {
            return $this->start_at;
        }

        // 前を取得
        $previous = $this->previous;
        // 前が存在しない場合、最小値を返す
        if (!isset($previous->id)) {
            return Carbon::minValue();
        }
        $previous_start_at_min = $previous->start_at->copy()->addMinutes(1);
        // 前がまだ開始していないか確認
        return $previous_start_at_min->lt($now) ? $now : $previous_start_at_min;
    }

    public function getStartAtMaxAttribute() : Carbon
    {
        $now = Carbon::now();
        // すでに保存済みで開始している場合、変更できないのでそのままの日時を渡す
        if (isset($this->id) && $this->start_at->lt($now)) {
            return $this->start_at;
        }

        // 前を取得
        $previous = $this->previous;
        // 前が存在しない場合、現在の日時-1分を返す
        if (!isset($previous->id)) {
            return Carbon::create($now->year, $now->month, $now->day, $now->hour, $now->minute, 0)
                ->addMinutes(-1);
        }

        $max = Carbon::parse('9999-12-31')->endOfMonth();
        // 次を取得
        $next = $this->next;
        // 次が存在しない場合、最大値を返す
        return isset($next->id) ? $next->start_at->copy()->addMinutes(-1) : $max;
    }

    public function getMessageListAttribute()
    {
        // 空の場合
        if (!isset($this->messages)) {
            return collect([]);
        }

        $message_list = [];
        $p_message_list = json_decode($this->messages);
        foreach ($p_message_list as $p_message) {
            $message_list[] = (object) ['start_at' => Carbon::parse($p_message->start_at), 'body' => $p_message->body];
        }
        return collect($message_list);
    }

    public function setMessageListAttribute($message_list)
    {
        $p_message_list = $message_list
            ->unique('start_at')
            ->sortBy('start_at')
            ->all();
        $data_list = [];
        foreach ($p_message_list as $p_message) {
            $data_list[] = (object) ['start_at' => $p_message->start_at->format('Y-m-d H:i:s'),
                'body' => $p_message->body == '' ? null : $p_message->body];
        }
        $this->messages = json_encode($data_list);
    }

    public function getOldMessageListAttribute()
    {
        $message_list = $this->message_list;
        $now = Carbon::now();
        return $message_list->filter(function ($value, $key) use ($now) {
            return $value->start_at->lt($now);
        });
    }

    public function getNextMessageListAttribute()
    {
        $message_list = $this->message_list;
        $now = Carbon::now();
        return $message_list->filter(function ($value, $key) use ($now) {
            return $value->start_at->gte($now);
        });
    }

    public function setNextMessageListAttribute($next_message_list)
    {
        $this->message_list = $this->old_message_list->merge($next_message_list);
    }

    public function getMessageStartAtMinAttribute() : Carbon
    {
        $now = Carbon::now();
        $message_list = $this->old_message_list;
        // メッセージが空の場合、交換先の開始日時に合わせる
        if ($message_list->isEmpty()) {
            return $this->start_at->max($now);
        }
        // 最後のメッセージが既に開始していた場合
        $last_message = $message_list->last();
        $start_at_min = $last_message->start_at->copy()->addMinutes(1);
        // 前がまだ開始していないか確認
        return $start_at_min->lt($now) ? $now : $start_at_min;
    }

    public function getMessageStartAtMaxAttribute() : Carbon
    {
        return $this->stop_at;
    }

    public function getMessageBodyAttribute()
    {
        $now = Carbon::now();
        $message_list = $this->message_list;
        while (true) {
            $message = $message_list->pop();
            if (!isset($message)) {
                return null;
            }
            if ($message->start_at->lt($now)) {
                return $message->body;
            }
        }
    }

    /**
     * 交換情報初期値取得.
     * @param int $type 交換先
     * @return ExchangeInfo 交換情報
     */
    public static function getDefault(int $type) : ExchangeInfo
    {
        $exchange_info = new self();
        $exchange_info->type = $type;
        $exchange_info->yen_rate = config('exchange.point.'.$type.'.default.yen_rate');
        $exchange_info->stop_at = Carbon::parse('9999-12-31')->endOfMonth();
        return $exchange_info;
    }

    /**
     * 交換先情報を保存.
     * @return bool 成功の場合はtrueを、失敗の場合はfalseを返す
     */
    public function saveExchangeInfo() : bool
    {
        if (!$this->isDirty()) {
            return true;
        }

        $exchange_info = $this;
        // 保存実行
        return DB::transaction(function () use ($exchange_info) {
            $now = Carbon::now();
            $previous = $exchange_info->previous;
            // 前の交換先の終了日時を設定
            if (isset($previous)) {
                $previous->stop_at = $exchange_info->start_at->copy()->subSeconds(1);
                $previous->save();
            }
            $exchange_info->save();
            return true;
        });
    }

    //メンテナンス用のデータを追加
    public function saveMaintenance(){
        $exchange_info_data = self::where('type', '=', ExchangeRequest::PAYPAY_TYPE)
                            ->where('status', '=', self::STOP_STATUS)
                            ->where('start_at', '<=', date('Y-m-d H:i'))
                            ->where('stop_at', '>=', date('Y-m-d H:i'))->get();

        if ($exchange_info_data->isEmpty()) {
            $exchange_info = self::getDefault(ExchangeRequest::PAYPAY_TYPE);
            $exchange_info->status = self::STOP_STATUS;
            $exchange_info->messages = '[]';
            $exchange_info->start_at = date('Y-m-d H:i:s');
            $exchange_info->saveExchangeInfo();
        }
    }
}
