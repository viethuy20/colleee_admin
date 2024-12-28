<?php
namespace App\Console\Program;

use App\AffReward;
use App\Program;
use App\ProgramStock;
use App\ProgramStockLog;
use App\ProgramStockBatchLog;
use Carbon\Carbon;

use App\Affiriate;
use App\Console\BaseCommand;
use WrapPhp;

class UpDownStock extends BaseCommand
{
    protected $tag = 'program:up_down_stock';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'program:up_down_stock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update up or down stock program';

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
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // タグ作成
        $this->info('start');
        $start = Carbon::now();

        //check run logs
        $listLogs = ProgramStockLog::all();

        // get list program for reduce quantity cv
        $listProgram = AffReward::select('programs.id', 'aff_rewards.status', 'order_id', 'aff_rewards.id as aff_reward_id', 'aff_rewards.updated_at' )
            ->where('aff_rewards.flag_stock', AffReward::FLAG_STOCK_OFF)
            ->whereIn('aff_rewards.status', [AffReward::REWARDED_STATUS, AffReward::CANCELED_STATUS, AffReward::WAITING_STATUS, AffReward::ACTIONED_STATUS])
            ->join('affiriates', function($join) {
                $join->on('aff_rewards.affiriate_id', '=', 'affiriates.id')
                    ->where('affiriates.parent_type', Affiriate::PROGRAM_TYPE)
                    ->whereNull('affiriates.deleted_at');
            })
            ->join('programs', function($join) {
                $join->on('affiriates.parent_id', '=', 'programs.id');
            });
        if (empty(WrapPhp::count($listLogs))) {
            //初回、バッチ実行時program_stock_logsテーブルが存在しない場合、成果データは見つからない
            $listProgram->whereDate('aff_rewards.updated_at', $start->toDateString());
        } else {
            $listProgram->whereDate('aff_rewards.updated_at', '>=', date('Y-m-d', strtotime($listLogs[WrapPhp::count($listLogs)-1]['end'])));
        }
        $listProgram = $listProgram->get();

        //reduce stock cv and update flag affReward
        foreach($listProgram as $program) {
            $programStock = ProgramStock::query()
                ->where('program_id', $program['id'])
                ->get()
                ->last();

            // 取り込み対象の成果で過去に発生した自動キャンセル（発生・配布待ち）を検索
            $exists = AffReward::where('aff_rewards.status', '=', AffReward::AUTO_CANCELED_STATUS)
            ->where('aff_rewards.created_at', '<', $start)
            ->where('programs.id', '=', $program['id'])
            ->where('aff_rewards.order_id', '=', $program['order_id'])
            ->join('affiriates', function($join) {
                $join->on('aff_rewards.affiriate_id', '=', 'affiriates.id')
                    ->where('affiriates.parent_type', Affiriate::PROGRAM_TYPE)
                    ->whereNull('affiriates.deleted_at');
            })
            ->join('programs', function($join) {
                $join->on('affiriates.parent_id', '=', 'programs.id');
            })->exists();

            // 取り込み対象の成果で過去に発生した自動キャンセル（発生・配布待ち）を検索
            // ※前回実行 - 今回実行を抽出し成果が存在する場合（在庫CV-1せず自動キャンセルが発生した場合）
            $exists2 = AffReward::where('aff_rewards.status', '=', AffReward::AUTO_CANCELED_STATUS);
            if (WrapPhp::count($listLogs) >= 1) {
                $exists2 = $exists2->where('aff_rewards.created_at', '>', $listLogs[WrapPhp::count($listLogs)-1]['end']);
            }
            $exists2 = $exists2->where('aff_rewards.created_at', '<=', $start)
                ->where('programs.id', '=', $program['id'])
                ->where('aff_rewards.order_id', '=', $program['order_id'])
                ->join('affiriates', function($join) {
                    $join->on('aff_rewards.affiriate_id', '=', 'affiriates.id')
                        ->where('affiriates.parent_type', Affiriate::PROGRAM_TYPE)
                        ->whereNull('affiriates.deleted_at');
                })
                ->join('programs', function($join) {
                    $join->on('affiriates.parent_id', '=', 'programs.id');
                })->exists();

            $is_program_stock = !is_null($programStock) && !is_null($programStock['stock_cv']);

            if ($program['status'] != AffReward::CANCELED_STATUS && $is_program_stock) {

                // 過去に自動キャンセルが有、かつ前回バッチ実行時間の間に自動キャンセルが有
                // 条件1. 発生 or 配布待ち or 配布
                // 条件2. 配布 and 自動キャンセル（前回実行 - 今回実行で在庫CV-1をしていない場合）
                if(((($program['status'] == AffReward::REWARDED_STATUS || $program['status'] == AffReward::ACTIONED_STATUS || $program['status'] == AffReward::WAITING_STATUS) && ($exists && $exists2))) ||
                   ((($program['status'] == AffReward::REWARDED_STATUS || $program['status'] == AffReward::ACTIONED_STATUS || $program['status'] == AffReward::WAITING_STATUS) && (!$exists && !$exists2)))
                ) {
                    ProgramStock::downStockCV($program['id']);
                }
                
                if ($programStock['stock_cv'] == 0) {
                    Program::updateStatus($program['id'],1);
                }
            } else if ($program['status'] == AffReward::CANCELED_STATUS && $is_program_stock) {

                if($exists && !$exists2) {
                    ProgramStock::upStockCV($program['id']);
                }
            }

            // Save log batch stock
            $programStockForBatch = ProgramStock::query()
                ->with('programStockBatchLog')
                ->where('program_id', $program['id'])
                ->get()
                ->last();

            if ($programStockForBatch) {
                if (!$programStockForBatch->programStockBatchLog) {
                    ProgramStockBatchLog::create([
                        'program_stock_id' => $programStockForBatch->id,
                        'time_run_batch' => $programStockForBatch->updated_at,
                    ]);
                } else {
                    $programStock->programStockBatchLog->update([
                        'time_run_batch' => $programStockForBatch->updated_at,
                        'status_notify' => 0,
                    ]);
                }
            }
            
            AffReward::updateFlagStock($program['aff_reward_id']);
        }

        //save log
        ProgramStockLog::createLog($start, Carbon::now(), '成功');

        //
        $this->info('success');

        return 0;
    }
}
