<?php
namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use DBTrait, PartitionTrait;

     /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'reports';

    /**
     * The guarded attributes on the model.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * 日付を変形する属性
     * @var array
     */
    protected $dates = ['target_at', 'created_at'];

    protected $casts = [
        'target_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * 更新日時更新停止.
     * @var bool
     */
    public $timestamps = false;

    /**
     * 対象日取得.
     * @return string|NULL 対象日
     */
    public function getTargetDateAttribute()
    {
        return isset($this->target_at) ? $this->target_at->format('Y-m-d') : null;
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
        $partition_expired = 6;
        // 予約パーティション数
        $reserved_partition = 4;

        return self::refreshMonthRange($db_name, $tb_name, $partition_expired, $reserved_partition);
    }

    /**
     * レポートを取得.
     * @param Carbon $date 対象日
     */
    public static function getReportList(Carbon $date)
    {
        $end_date = $date->copy()->endOfMonth();
        $start_date = $end_date->copy()->startOfMonth();
        $p_report_map = Report::whereBetween('target_at', [$start_date, $end_date])
            ->get()
            ->keyBy('target_date');

        $report_list = collect();
        $diff = $start_date->diffInDays($end_date);
        $diff = abs((int)$diff);
        $n = $diff + 1;

        for ($i = 0; $i < $n; ++$i) {
            $target_at = $start_date->copy()->addDays($i);
            $target_date = $target_at->format('Y-m-d');

            $report = $p_report_map[$target_date] ?? self::createReport($target_at);

            if (!isset($report)) {
                break;
            }
            $report_list->push($report);
        }

        return $report_list;
    }

    private static function createReport(Carbon $date)
    {
        // 未来のレポートは作らない
        $today = Carbon::today();
        if ($today->lt($date)) {
            return null;
        }
        $start = $date->copy()->startOfDay();

        $cur_report = self::where('target_at', '=', $start)
                ->first();
        // 既にレポートが存在する場合
        if (isset($cur_report->id)) {
            return $cur_report;
        }

        $data = [];
        // GMOペイメントゲートウェイの交換完了ポイント数
        $data['rakuten_bank_point'] = ExchangeRequest::ofBank()
            ->where('status', '=', 0)
            ->where('request_level', '=', 2)
            ->whereBetween('confirmed_at', [$start, $start->copy()->endOfDay()])
            ->sum('point');
        // Vovageの交換完了ポイント数
        $data['voyage_point'] = ExchangeRequest::ofVoyageGiftCode()
            ->where('status', '=', 0)
            ->where('request_level', '=', 1)
            ->whereBetween('requested_at', [$start, $start->copy()->endOfDay()])
            ->sum('point');
        // NTTの交換完了ポイント数
        $data['ntt_point'] = ExchangeRequest::ofNttCardGiftCode()
            ->where('status', '=', 0)
            ->where('request_level', '=', 1)
            ->whereBetween('requested_at', [$start, $start->copy()->endOfDay()])
            ->sum('point');
        // dポイントの交換完了ポイント数
        $data['d_point'] = ExchangeRequest::ofDPoint()
            ->where('status', '=', 0)
            ->where('request_level', '=', 1)
            ->whereBetween('requested_at', [$start, $start->copy()->endOfDay()])
            ->sum('point');
        // LINE Payの交換完了ポイント数
        $data['line_pay'] = ExchangeRequest::ofLinePay()
            ->where('status', '=', 0)
            ->where('request_level', '=', 1)
            ->whereBetween('requested_at', [$start, $start->copy()->endOfDay()])
            ->sum('point');
        // PayPayの交換完了ポイント数
        $data['paypay'] = ExchangeRequest::ofPaypay()
        ->where('status', '=', 0)
        ->where('request_level', '=', 1)
        ->whereBetween('requested_at', [$start, $start->copy()->endOfDay()])
        ->sum('point');

        // KDOLの交換完了ポイント数
        $data['kdol'] = ExchangeRequest::OfKdol()
        ->where('status', '=', 0)
        ->where('request_level', '=', 1)
        ->whereBetween('requested_at', [$start, $start->copy()->endOfDay()])
        ->sum('point');

        // デジタルギフトPayPalの交換完了ポイント数
        $data['paypal'] = ExchangeRequest::ofDigitalGiftPaypal()
        ->where('status', '=', 0)
        ->where('request_level', '=', 1)
        ->whereBetween('requested_at', [$start, $start->copy()->endOfDay()])
        ->sum('point');

        // デジタルギフトJALマイルの交換完了ポイント数
        $data['jalmile'] = ExchangeRequest::ofDigitalGiftJalMile()
        ->where('status', '=', 0)
        ->where('request_level', '=', 1)
        ->whereBetween('requested_at', [$start, $start->copy()->endOfDay()])
        ->sum('point');
        $report = new self();
        $report->fill(['target_at' => $start, 'data' => json_encode((object) $data), 'created_at' => Carbon::now()]);

        //
        if ($today->gt($date)) {
            $report->save();
        }
        return $report;
    }
}
