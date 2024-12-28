<?php
namespace App\Console\NttCard;

use Carbon\Carbon;
use App\Console\BaseCommand;
use App\ExchangeRequest;

/**
 * Description of Expire
 *
 * @author t_moriizumi
 */
class Expire extends BaseCommand {
    protected $tag = 'ntt_card:expire';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ntt_card:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire ntt_card';

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
        $expire_at = Carbon::today()->addDays(-90);
        $exchange_request_id = 0;
        while (true) {
            // 発行済みギフト券申し込み一覧を取得
            $exchange_request_id_list = ExchangeRequest::where('type', '=', ExchangeRequest::AMAZON_GIFT_TYPE)
                ->where('status', '=', ExchangeRequest::SUCCESS_STATUS)
                ->where('requested_at', '<', $expire_at)
                ->where('id', '>', $exchange_request_id)
                ->orderBy('id', 'asc')
                ->take(1000)
                ->pluck('id')
                ->all();

            //　なくなったら終了
            if (empty($exchange_request_id_list)) {
                break;
            }
            $exchange_request_id = max($exchange_request_id_list);

            \Log::info('Stop exchange_request:['.implode(',', $exchange_request_id_list).']');
            // DBからコードを削除
            ExchangeRequest::whereIn('id', $exchange_request_id_list)
                ->update(['status' => ExchangeRequest::STOP_STATUS, 'response' => null]);
        }
        //
        $this->info('success');
        return 0;
    }
}
