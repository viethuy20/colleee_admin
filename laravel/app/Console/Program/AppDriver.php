<?php
namespace App\Console\Program;

use Carbon\Carbon;

use App\Affiriate;
use App\Console\BaseCommand;

class AppDriver extends BaseCommand
{
    protected $tag = 'program:app_driver';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'program:app_driver';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh AppDriver program';

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

        $now = Carbon::now();

        // アフィリエイト情報一覧取得
        $affiriate_map = Affiriate::where('start_at', '<=', $now)
            ->where('stop_at', '>=', $now)
            ->where('parent_type', '=', Affiriate::PROGRAM_TYPE)
            ->where('asp_id', '=', 26)
            ->whereIn('parent_id', function ($query) {
                $query->select('id')
                    ->from('programs')
                    ->where('status', '=', 0);
            })
            ->get()
            ->keyBy('asp_affiriate_id')
            ->all();

        // AppDriverアフィリエイトが存在する場合、アフィリエイト情報を確認
        if (!empty($affiriate_map)) {
            // キャッシュなしで検索
            $app_driver_response = \App\External\AppDriver::search(false);

            foreach ($app_driver_response->campaign as $campaign) {
                // 検索中の広告でない場合はスキップ
                if (!isset($affiriate_map[$campaign->id])) {
                    continue;
                }
                $affiriate = $affiriate_map[$campaign->id];
                $program = $affiriate->program;

                // プログラムの掲載期間と一致する場合はスキップ
                if ($program->start_at->eq($campaign->start_time) && $program->stop_at->eq($campaign->end_time)) {
                    continue;
                }
                // 掲載期間を更新
                $program->start_at = $campaign->start_time;
                $program->stop_at = $campaign->end_time;
                $program->save();
            }
        }

        //
        $this->info('success');

        return 0;
    }
}
