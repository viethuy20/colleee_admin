<?php
namespace App\Console\ExchangeRequest;

use Carbon\Carbon;

use App\Csv;

use App\Console\BaseCommand;
use App\ExchangeRequest;

use App\External\RakutenBank;
use WrapPhp;

class Import extends BaseCommand
{
    protected $tag = 'exchange_request:import';
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exchange_request:import {admin_id} {status} {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import exchange request';
    
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
        
        $admin_id = $this->argument('admin_id');
        $status = $this->argument('status');
        $file_path = $this->argument('file');
        
        // CSVファイルが存在しなかった場合
        if (!file_exists($file_path)) {
            $this->info(sprintf('Csv file not found.[%s]', $file_path));
            $this->info('falied');
            return 1;
        }
        
        $file = new Csv\SplFileObject($file_path, 'r');
        while (true) {
            // パース
            if (($data = $file->fgetcsv()) === false || WrapPhp::count($data) < 1 || $data[0] == '') {
                break;
            }
            
            // 交換申し込みID取得
            $exchange_request_id = ExchangeRequest::getIdByNumber($data[0]);
            // 交換申し込み取得
            $exchange_request = ExchangeRequest::where('id', '=', $exchange_request_id)
                ->whereIn('status', [ExchangeRequest::WAITING_STATUS, ExchangeRequest::ERROR_STATUS])
                ->first();
            // 存在しない場合
            if (!isset($exchange_request->id)) {
                continue;
            }
            // 組戻し
            if ($status == ExchangeRequest::ROLLBACK_STATUS) {
                $exchange_request->rollbackRequest($admin_id);
                continue;
            }
            // 金融機関,ドットマネーの場合のみ承認
            if (in_array($exchange_request->type, [ExchangeRequest::BANK_TYPE, ExchangeRequest::DOT_MONEY_POINT_TYPE], true) && $status == ExchangeRequest::SUCCESS_STATUS) {
                $exchange_request->approvalRequest();
                continue;
            }
        }
        
        //
        $this->info('success');
        
        return 0;
    }
}