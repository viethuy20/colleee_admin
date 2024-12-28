<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Throwable;

class AffRewardExportCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * === option month ===
     * month = 0: current month (Ex: February)
     * month = 1: current month + 1 (Ex: March)
     * month = -1: current month -1 (Ex January)
     * 
     * 
     * === option all ===
     * all = false: export data in month
     * all = true: export data from start month to all
     * @var string
     */
    protected $signature = 'aff_reward:export-csv {--month=0} {--all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export data aff_reward in month';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    private $programIds;
    private $aspIds;
    private $setDate;

    public function __construct()
    {
        parent::__construct();
        $this->programIds = [
            5017, 5016, 6304, 6305, 5461,
            5462, 5435, 5436, 5441, 5398,
            6125, 6124, 6317, 6318, 2049,
            6611, 2062, 5476, 5584, 5993,
            6309, 6027, 5415, 2320, 4709,
            1196, 1121, 3750, 1773, 3738,
            4708, 1205, 2120, 1372, 1327,
            1022, 1034, 3668, 4791, 6278,
            6225, 6268, 4781, 4091, 6267,
            6266, 6757, 3678, 5238, 5440,
            5237, 5312, 6815, 6537, 6319,
            6765, 5311, 5422, 6907, 6657,
            6952, 6307, 3608, 6461, 6621,
            2446, 4575, 4610, 5390, 6484,
            6457, 7099, 7098, 6506, 6505,
        ];
        $this->aspIds = [
            35,
            41
        ];
        $this->setDate = [
            'dev' => '2023-06-01',
            'prod' => '2023-06-27',
        ];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Use transaction
        DB::beginTransaction();
        DB::enableQueryLog();

        try {
            switch ($_ENV['APP_ENV']) {
                case 'production':
                    $setDate = $this->setDate['prod'];
                    break;

                default:
                    $setDate = $this->setDate['dev'];
                    break;
            }

            // Option value
            $monthOption = (int) $this->option('month');
            $allOption = (bool) $this->option('all');

            if ($setDate && !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $setDate)) {
                $this->warn("Option 'setDate' is invalid.");
                return 0;
            }

            switch ($setDate) {
                case "":
                    if (!is_int($monthOption)) {
                        $this->warn("Option 'month' is invalid.");
                        return 0;
                    }

                    // Date between
                    switch (true) {
                        case $monthOption == 0:
                            $start = Carbon::now()->startOfMonth();
                            $end = Carbon::now()->endOfMonth();
                            break;

                        default:
                            $start = Carbon::now()->addMonths($monthOption)->startOfMonth();
                            $end = Carbon::now()->addMonths($monthOption)->endOfMonth();
                            break;
                    }
                    break;

                default:
                    $start = Carbon::parse($setDate);
                    $end = Carbon::parse($setDate)->endOfMonth();

            }

            if ($allOption) {
                $end = "all";
            }

            $this->info("Start export aff_reward data from {$start} to {$end}.");

            // AffReward data
            $queryDB = DB::table('aff_rewards')
                ->leftjoin('asps', function ($join) {
                    $join->on(
                        'aff_rewards.asp_id',
                        '=',
                        'asps.id'
                    )
                        ->whereNull('asps.deleted_at');
                })
                ->leftjoin('affiriates', function ($join) {
                    $join->on(
                        'aff_rewards.affiriate_id',
                        '=',
                        'affiriates.id'
                    )
                        ->whereNull('affiriates.deleted_at');
                })
                ->leftjoin('programs', function ($join) {
                    $join->on(
                        'affiriates.parent_id',
                        '=',
                        'programs.id'
                    );
                })
                ->select(
                    // aff_rewards
                    'aff_rewards.id AS aff_reward_id',
                    'aff_rewards.created_at AS aff_reward_created_at',
                    'aff_rewards.asp_affiriate_id AS aff_reward_asp_affiriate_id',
                    'aff_rewards.order_id AS aff_reward_order_id',
                    'aff_rewards.user_id AS aff_reward_user_id',
                    'aff_rewards.affiriate_id AS aff_reward_affiriate_id',
                    'aff_rewards.aff_course_id AS aff_reward_aff_course_id',
                    'aff_rewards.course_name AS aff_reward_course_name',
                    'aff_rewards.title AS aff_reward_title',
                    'aff_rewards.code AS aff_reward_code',
                    'aff_rewards.status AS aff_reward_status',
                    'aff_rewards.actioned_at AS aff_reward_actioned_at',
                    'aff_rewards.price AS aff_reward_price',
                    'aff_rewards.point AS aff_reward_point',
                    'aff_rewards.diff_point AS aff_reward_diff_point',
                    'aff_rewards.bonus_point AS aff_reward_bonus_point',
                    'aff_rewards.status_updated_at AS aff_reward_status_updated_at',
                    'aff_rewards.data AS aff_reward_data',
                    'aff_rewards.admin_id AS aff_reward_admin_id',
                    'aff_rewards.old AS aff_reward_old',
                    'aff_rewards.flag_stock AS aff_reward_flag_stock',
                    'aff_rewards.confirmed_at AS aff_reward_confirmed_at',
                    'aff_rewards.updated_at AS aff_reward_updated_at',

                    // asps
                    'asps.name AS asp_name',

                    // affiriates
                    'affiriates.start_at AS affiriate_start_at',

                    // programs
                    'programs.id AS program_id',
                );

            $queryDB->when($allOption === false, function ($query) use ($start, $end) {
                return $query->whereBetween('aff_rewards.created_at', [$start, $end]);
            });

            $queryDB->when($allOption === true, function ($query) use ($start) {
                return $query->whereDate('aff_rewards.created_at', '>=', $start);
            });

            $affRewards = $queryDB
                ->where(function ($query) {
                    $query->whereIn('programs.id', $this->programIds)
                        ->orWhereIn('aff_rewards.asp_id', $this->aspIds);
                })
                ->orderBy('aff_rewards.created_at', 'DESC')
                ->get();

            $this->info("Data count: {$affRewards->count()}");
            Log::info(DB::getQueryLog());

            // Csv file name
            $partern = 'Y-m-d';
            $now = Carbon::now();
            $csvName = "aff_reward_{$now->format($partern)}.csv";
            $csvS3PathFile = $csvName;
            $csvLocalPathFile = storage_path('app/public/') . $csvName;

            // Create csv file
            $initFields = [
                'id',
                'created_at',
                'ASP', // asp name
                'プログラムID', // program id
                'asp_affiriate_id',
                'order_id',
                'user_id',
                'affiriate_id',
                'aff_course_id',
                'course_name',
                'title',
                'code',
                'status',
                'actioned_at',
                'price',
                'point',
                'diff_point',
                'bonus_point',
                'status_updated_at',
                'data',
                'admin_id',
                'old',
                'flag_stock',
                'confirmed_at',
            ];

            $BOM = "\xEF\xBB\xBF"; // UTF-8 BOM
            $fp = fopen($csvLocalPathFile, 'w');

            fwrite($fp, $BOM);
            fputcsv($fp, $initFields);

            foreach ($affRewards as $affReward) {
                switch ($affReward->aff_reward_status) {
                    case 0:
                        $status = '配布済み';
                        break;

                    case 1:
                        $status = 'キャンセル';
                        break;

                    case 2:
                        $status = '異常';
                        break;

                    case 3:
                        $status = '発生';
                        break;

                    case 4:
                        $status = '発生';
                        break;

                    case 5:
                        $status = '自動キャンセル';
                        break;

                    default:
                        $status = '要確認';
                        break;
                }

                $dataExport = [
                    $affReward->aff_reward_id,
                    $affReward->aff_reward_created_at,
                    $affReward->asp_name ?? '不要',
                    $affReward->program_id ?? 'なし',
                    $affReward->aff_reward_asp_affiriate_id,
                    $affReward->aff_reward_order_id,
                    $affReward->aff_reward_user_id,
                    $affReward->aff_reward_affiriate_id,
                    $affReward->aff_reward_aff_course_id,
                    $affReward->aff_reward_course_name,
                    $affReward->aff_reward_title,
                    $affReward->aff_reward_code,
                    $status, // $affReward->aff_reward_status
                    $affReward->aff_reward_actioned_at,
                    $affReward->aff_reward_price,
                    $affReward->aff_reward_point,
                    $affReward->aff_reward_diff_point,
                    $affReward->aff_reward_bonus_point,
                    $affReward->aff_reward_status_updated_at,
                    $affReward->aff_reward_data,
                    $affReward->aff_reward_admin_id,
                    $affReward->aff_reward_old,
                    $affReward->aff_reward_flag_stock,
                    $affReward->aff_reward_confirmed_at,
                ];
                fputcsv($fp, $dataExport);
            }

            fclose($fp);

            // Put csv in s3 AWS
            Storage::disk('s3')->getDriver()->put($csvS3PathFile, fopen($csvLocalPathFile, 'r'), [
                'ContentType' => 'text/csv',
            ]);

            DB::commit();
            $this->info("End export file.");

        } catch (Throwable $th) {
            $this->error("Export file error: {$th->getMessage()}");
            Log::error("Error at file: app\Console\Commands\AffRewardExportCsv.php", [
                "message" => $th->getMessage()
            ]);
            DB::rollBack();
        }

        return 0;
    }
}