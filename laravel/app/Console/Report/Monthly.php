<?php
namespace App\Console\Report;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use function PHPSTORM_META\type;

use App\Console\BaseCommand;
use App\ExchangeRequest;
use App\External\MountManager;
use App\User;
use App\UserPoint;

class Monthly extends BaseCommand
{
    protected $tag = 'report:monthly';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:monthly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create monthly report';

    private $current_row = 0;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * ポイント分類を作成.
     * @param Worksheet $workSheet ワークシート
     * @param Carbon $first_datetime_last_month 前月月初日
     * @param Carbon $last_datetime_last_month 前月月末日
     */
    private function makePointClassification(
        Worksheet $workSheet,
        Carbon $first_datetime_last_month,
        Carbon $last_datetime_last_month
    ) {
        //配付データ取得
        $haifu = UserPoint::select(
            'type',
            DB::raw('SUM(diff_point) AS sum_diff_point'),
            DB::raw('SUM(bonus_point) AS sum_bonus_point')
        )
            ->whereBetween(
                'created_at',
                [$first_datetime_last_month, $last_datetime_last_month]
            )
            ->groupBy('type')
            ->get();

        //使用データ取得
        $shiyo = ExchangeRequest::select(
            'type',
            'status',
            DB::raw('SUM(point) AS sum_point')
        )
            ->whereBetween(
                'created_at',
                [$first_datetime_last_month, $last_datetime_last_month]
            )
            ->groupBy(['type', 'status'])
            ->get();

        //出力用データに値をセット
        $output_data = [];
        //広告参加
        $program_data = $haifu->where('type', UserPoint::PROGRAM_TYPE)->first();
        $output_data[0] = isset($program_data) ? $program_data['sum_diff_point']: 0;
        //モニター
        $haifu_monitor = $haifu->where('type', UserPoint::MONITOR_TYPE)->first();
        $output_data[1] = isset($haifu_monitor) ?
            ($haifu_monitor['sum_diff_point'] + $haifu_monitor['sum_bonus_point']) : 0;
        //アンケート
        $haifu_enquete = $haifu->where('type', UserPoint::QUESTION_TYPE)->first();
        $output_data[2] = isset($haifu_enquete) ?
            ($haifu_enquete['sum_diff_point'] + $haifu_enquete['sum_bonus_point']) : 0;
        //予想広告参加
        $haifu_old_program = $haifu->where('type', UserPoint::OLD_PROGRAM_TYPE)->first();
        $output_data[3] = isset($haifu_old_program) ?
            ($haifu_old_program['sum_diff_point'] + $haifu_old_program['sum_bonus_point']) : 0;
        //成果あり特別広告
        $haifu_sp_reward = $haifu->where('type', UserPoint::SP_PROGRAM_WITH_REWARD_TYPE)->first();
        $output_data[4] = isset($haifu_sp_reward) ?
            ($haifu_sp_reward['sum_diff_point'] + $haifu_sp_reward['sum_bonus_point']) : 0;

        //口コミ
        $haifu_review = $haifu->where('type', UserPoint::REVIEW_TYPE)->first();
        $output_data[12] = isset($haifu_review) ?
            ($haifu_review['sum_diff_point'] + $haifu_review['sum_bonus_point']) : 0;
        //特別広告
        $haifu_sp = $haifu->where('type', UserPoint::SP_PROGRAM_TYPE)->first();
        $output_data[13] = isset($haifu_sp) ?
            ($haifu_sp['sum_diff_point'] + $haifu_sp['sum_bonus_point']) : 0;
        //誕生日ボーナス
        $haifu_birthday = $haifu->where('type', UserPoint::BIRTYDAY_BONUS_TYPE)->first();
        $output_data[14] = isset($haifu_birthday) ?
            ($haifu_birthday['sum_diff_point'] + $haifu_birthday['sum_bonus_point']) : 0;
        //広告ボーナス
        $haifu_program_bonus = $haifu->where('type', UserPoint::PROGRAM_BONUS_TYPE)->first();
        $output_data[15] = isset($haifu_program_bonus) ?
            ($haifu_program_bonus['sum_diff_point'] + $haifu_program_bonus['sum_bonus_point']) : 0;
        //お友達紹介ボーナス
        $haifu_entry_bonus = $haifu->where('type', UserPoint::ENTRY_BONUS_TYPE)->first();
        $output_data[16] = isset($haifu_entry_bonus) ?
            ($haifu_entry_bonus['sum_diff_point'] + $haifu_entry_bonus['sum_bonus_point']) : 0;
        //ゲーム
        $haifu_game_bonus = $haifu->where('type', UserPoint::GAME_BONUS_TYPE)->first();
        $output_data[17] = isset($haifu_game_bonus) ?
            ($haifu_game_bonus['sum_diff_point'] + $haifu_game_bonus['sum_bonus_point']) : 0;
        //広告参加ボーナス
        $output_data[18] = $haifu->where('type', UserPoint::PROGRAM_TYPE)->first()['sum_bonus_point'] ?? 0;
        //ブログ申請
        $output_data[19] = $haifu->where('type', UserPoint::ADMIN_TYPE)->first()['sum_bonus_point'] ?? 0;

        //金融機関
        $output_data[21] = -$shiyo->where('type', ExchangeRequest::BANK_TYPE)->sum('sum_point') ?? 0;
        //Edy
        $output_data[22] = -$shiyo->where('type', ExchangeRequest::EDY_GIFT_TYPE)->sum('sum_point') ?? 0;
        //Amazon
        $output_data[23] = -$shiyo->where('type', ExchangeRequest::AMAZON_GIFT_TYPE)->sum('sum_point') ?? 0;
        //iTunes
        $output_data[24] = -$shiyo->where('type', ExchangeRequest::ITUNES_GIFT_TYPE)->sum('sum_point') ?? 0;
        //PEX
        $output_data[25] = -$shiyo->where('type', ExchangeRequest::PEX_GIFT_TYPE)->sum('sum_point') ?? 0;
        //GooglePlay
        $output_data[26] = -$shiyo->where('type', ExchangeRequest::GOOGLE_PLAY_GIFT_TYPE)->sum('sum_point') ?? 0;
        //NANACOポイント
        $output_data[27] = -$shiyo->where('type', ExchangeRequest::NANACO_GIFT_TYPE)->sum('sum_point') ?? 0;
        //.moneyポイント
        $output_data[28] = -$shiyo->where('type', ExchangeRequest::DOT_MONEY_POINT_TYPE)->sum('sum_point') ?? 0;
        //WAON
        $output_data[29] = -$shiyo->where('type', ExchangeRequest::WAON_GIFT_TYPE)->sum('sum_point') ?? 0;
        //dポイント
        $output_data[30] = -$shiyo->where('type', ExchangeRequest::D_POINT_TYPE)->sum('sum_point') ?? 0;
        // LINE Pay
        $output_data[31] = -$shiyo->where('type', ExchangeRequest::LINE_PAY_TYPE)->sum('sum_point') ?? 0;
        // Pontaポイント
        $output_data[32] = -$shiyo->where('type', ExchangeRequest::PONTA_GIFT_TYPE)->sum('sum_point') ?? 0;
        // プレイステーション ストアチケット
        $output_data[33] = -$shiyo->where('type', ExchangeRequest::PSSTICKET_GIFT_TYPE)->sum('sum_point') ?? 0;
        //paypayポイント
        $output_data[34] = -$shiyo->where('type', ExchangeRequest::PAYPAY_TYPE)->sum('sum_point') ?? 0;

        //デジタルギフトpaypalポイント
        $output_data[35] = -$shiyo->where('type', ExchangeRequest::DIGITAL_GIFT_PAYPAL_TYPE)->sum('sum_point') ?? 0;

        //JALマイルポイント
        $output_data[36] = -$shiyo->where('type', ExchangeRequest::DIGITAL_GIFT_JAL_MILE_TYPE)->sum('sum_point') ?? 0;

        //kdolポイント
        $output_data[37] = -$shiyo->where('type', ExchangeRequest::KDOL_TYPE)->sum('sum_point') ?? 0;

        // 連携先を増やしたらここから先keyが1個ずれる

        //金融機関
        $shiyo_rollback = $shiyo->where('status', ExchangeRequest::ROLLBACK_STATUS);
        $output_data[39] = $shiyo_rollback->where('type', ExchangeRequest::BANK_TYPE)->sum('sum_point') ?? 0;
        //Edy
        $output_data[40] = $shiyo_rollback->where('type', ExchangeRequest::EDY_GIFT_TYPE)->sum('sum_point') ?? 0;
        //Amazon
        $output_data[41] = $shiyo_rollback->where('type', ExchangeRequest::AMAZON_GIFT_TYPE)->sum('sum_point') ?? 0;
        //iTunes
        $output_data[42] = $shiyo_rollback->where('type', ExchangeRequest::ITUNES_GIFT_TYPE)->sum('sum_point') ?? 0;
        //PEX
        $output_data[43] = $shiyo_rollback->where('type', ExchangeRequest::PEX_GIFT_TYPE)->sum('sum_point') ?? 0;
        //GooglePlay
        $output_data[44] = $shiyo_rollback->where('type', ExchangeRequest::GOOGLE_PLAY_GIFT_TYPE)
            ->sum('sum_point') ?? 0;
        //NANACOポイント
        $output_data[45] = $shiyo_rollback->where('type', ExchangeRequest::NANACO_GIFT_TYPE)->sum('sum_point') ?? 0;
        //.moneyポイント
        $output_data[46] = $shiyo_rollback->where('type', ExchangeRequest::DOT_MONEY_POINT_TYPE)->sum('sum_point') ?? 0;
        //WAON
        $output_data[47] = $shiyo_rollback->where('type', ExchangeRequest::WAON_GIFT_TYPE)->sum('sum_point') ?? 0;
        //dポイント
        $output_data[48] = $shiyo_rollback->where('type', ExchangeRequest::D_POINT_TYPE)->sum('sum_point') ?? 0;
        // LINE Pay
        $output_data[49] = $shiyo_rollback->where('type', ExchangeRequest::LINE_PAY_TYPE)->sum('sum_point') ?? 0;
        // Pontaポイント
        $output_data[50] = $shiyo_rollback->where('type', ExchangeRequest::PONTA_GIFT_TYPE)->sum('sum_point') ?? 0;
        // プレイステーション ストアチケット
        $output_data[51] = $shiyo_rollback->where('type', ExchangeRequest::PSSTICKET_GIFT_TYPE)->sum('sum_point') ?? 0;
        //paypayポイント
        $output_data[52] = $shiyo_rollback->where('type', ExchangeRequest::PAYPAY_TYPE)->sum('sum_point') ?? 0;
        
        //デジタルギフトpaypalポイント
        $output_data[53] = $shiyo_rollback->where('type', ExchangeRequest::DIGITAL_GIFT_PAYPAL_TYPE)->sum('sum_point') ?? 0;

        //JALマイルポイント
        $output_data[54] = $shiyo_rollback->where('type', ExchangeRequest::DIGITAL_GIFT_JAL_MILE_TYPE)->sum('sum_point') ?? 0;

        //kdolポイント
        $output_data[55] = $shiyo_rollback->where('type', ExchangeRequest::KDOL_TYPE)->sum('sum_point') ?? 0;
        
        //組戻し（分類不明分)
        $output_data[57] = $haifu->where('type', UserPoint::ROLLBACK_TYPE)->first()['sum_diff_point'] ?? 0;
        
        // 一個ずれる

        //ポイント失効
        $output_data[59] = $haifu->where('type', UserPoint::ADMIN_TYPE)->first()['sum_diff_point'] ?? 0;

        //セルに出力
        foreach ($output_data as $offset => $value) {
            $row = $this->current_row + $offset;
            $workSheet->setCellValueExplicit(
                'E' . $row,
                $value,
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC
            );
        }

        //組戻し（分類不明分)セルに出力(2個ずれる)
        $offset = 57;
        $row = $this->current_row + $offset;
        $start_row = $this->current_row + 39;
        $end_row = $this->current_row + 55;
        $workSheet->setCellValueExplicit(
            'E' . $row,
            '=' . $output_data[$offset] . '- SUM(E' . $start_row . ':E' . $end_row . ')',
            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_FORMULA
        );
        $this->current_row += 67;
    }

    /**
     * 階層別ポイントを設定.
     * @param Worksheet $workSheet ワークシート
     * @param Carbon $last_datetime_last_month 前月月末日
     */
    private function classifyByNumOfPoints(
        Worksheet $workSheet,
        Carbon $last_datetime_last_month
    ) {

        //point/1000の商で分類
        $divided_by_1000 = UserPoint::select(
            DB::raw('SUM(point) AS pt'),
            DB::raw('COUNT(user_id) AS ut'),
            DB::raw('(point DIV 1000) AS dd')
        )
            ->whereIn(
                DB::raw('(id, user_id)'),
                function ($query) use ($last_datetime_last_month) {
                    $query->select(DB::raw('MAX(id)'), 'user_id')
                        ->from('user_points')
                        ->where('created_at', '<=', $last_datetime_last_month)
                        ->groupBy('user_id');
                }
            )
            ->where('point', '>', 0)
            ->groupBy('dd')
            ->get();

        //出力用データ初期化
        $output_data = [];
        for ($i = 0; $i < 10; $i++) {
            // 0から9,999まで
            $d1 = $divided_by_1000->where('dd', '=', $i)->first();
            $output_data[$i * 1000] = [$d1->pt ?? 0, $d1->ut ?? 0];

            if ($i > 0) {
                // 10,000から99,999まで
                $d2 = $divided_by_1000->whereBetween('dd', [$i * 10, ($i * 10) + 9]);
                $output_data[$i * 10000] = [$d2->sum('pt'), $d2->sum('ut')];
            }
        }
        // 100,000から149,999まで
        $d3 = $divided_by_1000->whereBetween('dd', [100, 149]);
        $output_data[100000] = [$d3->sum('pt'), $d3->sum('ut')];
        // 150,000以上
        $d4 = $divided_by_1000->where('dd', '>=', 150);
        $output_data[150000] = [$d4->sum('pt'), $d4->sum('ut')];
        // ソート
        ksort($output_data);

        $amount_list = array_keys($output_data);

        // セルに書き込み
        foreach ($amount_list as $i => $amount) {
            $row = $this->current_row + $i;

            // ラベル書き込み
            $next_amount = $amount_list[$i + 1] ?? null;
            $workSheet->setCellValueExplicit(
                'B' . $row,
                number_format($amount) . (isset($next_amount) ? ' - ' . number_format($next_amount - 1) : '以上'),
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
            );

            // データ書き込み
            $data = $output_data[$amount];
            foreach ($data as $j => $value) {
                $workSheet->setCellValueExplicit(
                    chr(ord('C') + $j) . $row,
                    $value,
                    \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC
                );
            }
        }
        $this->current_row += 29;
    }

    /**
     * 世代別ユーザー数を設定.
     * @param Worksheet $workSheet ワークシート
     * @param Carbon $last_datetime_last_month 前月月末日時
     */
    private function makeGenerationTable(
        Worksheet $workSheet,
        Carbon $last_datetime_last_month
    ) {
        $last_datetime_this_month = $last_datetime_last_month->copy()->addDay()->endOfMonth();
        $base_datetime_birthday = $last_datetime_this_month->copy()->startOfMonth()->subYear(120)->endOfMonth();
        $generation_data = User::selectRaw(
            'TIMESTAMPDIFF(YEAR, birthday, ?) DIV 10 AS agegroup, sex, COUNT(*) AS total',
            [$last_datetime_last_month]
        )
            ->where('created_at', '<=', $last_datetime_this_month)
            ->where(
                function ($query) use ($last_datetime_last_month) {
                    $query->whereNull('deleted_at')
                        ->orWhere('deleted_at', '>', $last_datetime_last_month);
                }
            )
            ->whereBetween('birthday', [$base_datetime_birthday, $last_datetime_this_month])
            ->groupBy('sex', 'agegroup')
            ->get();

        $sex_list = [1, 2, 0];
        //データセット
        for ($age_group = 0; $age_group <= 11; $age_group++) {
            $row = $this->current_row + $age_group;
            // ラベル
            $workSheet->setCellValueExplicit(
                'B' . $row,
                $age_group < 1 ? '10歳未満' : ($age_group * 10).'代',
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
            );
            //世代別ユーザー数合計欄
            $workSheet->setCellValueExplicit(
                'F' . $row,
                '=SUM(C' . $row . ':E' . $row . ')',
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_FORMULA
            );
            
            $g = $generation_data->where('agegroup', '=', $age_group);
            foreach ($sex_list as $i => $sex) {
                $d = $g->where('sex', '=', $sex)->first();
                $workSheet->setCellValueExplicit(
                    chr(ord('C') + $i) . $row,
                    $d->total ?? 0,
                    \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC
                );
            }
        }
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // タグ作成
        $this->info('start');
        $first_datetime_last_month = Carbon::today()->startOfMonth()->subMonth(1);
        $last_datetime_last_month = $first_datetime_last_month->copy()->endOfMonth();
        $last_date_last_month = $last_datetime_last_month->format('Ymd');

        //ファイル読み込み
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xls');
        $workBook = $reader->load(resource_path('monthly_report.xls'));

        $workSheet = $workBook->getSheetByName("colleee(Ymd)");
        $workSheet->setTitle("colleee($last_date_last_month)");

        //対象期間セット
        $this->current_row += 5;
        $workSheet->setCellValueExplicit(
            'B' . $this->current_row,
            $first_datetime_last_month,
            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
        );
        $workSheet->setCellValueExplicit(
            'C' . $this->current_row,
            $last_datetime_last_month,
            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
        );
        $workSheet->setCellValueExplicit(
            'E' . $this->current_row,
            User::where(function ($query) use ($last_datetime_last_month) {
                $query->whereNull('deleted_at')
                    ->orWhere('deleted_at', '>=', $last_datetime_last_month);
            })->where('created_at', '<=', $last_datetime_last_month)
                ->count(),
            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC
        );
        $workSheet->setCellValueExplicit(
            'F' . $this->current_row,
            User::where('created_at', '>=', $first_datetime_last_month)
                ->where('created_at', '<=', $last_datetime_last_month)
                ->count(),
            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC
        );

        $this->current_row += 5;

        //ポイント分類
        $this->makePointClassification($workSheet, $first_datetime_last_month, $last_datetime_last_month);

        //階層別のポイント数と人数
        $this->classifyByNumOfPoints($workSheet, $last_datetime_last_month);

        //世代別ユーザー数
        $this->makeGenerationTable($workSheet, $last_datetime_last_month);

        //前々月合計をcheckSheetに設定

        $cell = 'C100';
        $two_month_ago_total = $workSheet->getCell($cell)->getCalculatedValue();
        $workSheet = $workBook->getSheetByName("checkSheet");
        $workSheet->setCellValueExplicit(
            'B3',
            $two_month_ago_total,
            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC
        );

        // ディレクトリを取得
        $dir_path = config('path.backup');
        if (!file_exists($dir_path)) {
            mkdir($dir_path, '755');
        }
        //書き込みファイル
        $file_path = $dir_path.DIRECTORY_SEPARATOR.sprintf("monthly_%d.xls", $last_date_last_month);
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xls($workBook);
        $writer->save($file_path);

        // ファイルをマウント
        MountManager::mount(MountManager::REPORT_TYPE, $file_path);

        // ファイルを削除
        @unlink($file_path);

        $this->info('success');
        return 0;
    }
}
