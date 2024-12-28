<?php
namespace App\Console\Bank;

use App\Csv;

use DB;
use WrapPhp;
use App\Console\BaseCommand;
use App\Bank;
use App\BankBranch;

class Import extends BaseCommand
{
    protected $tag = 'bank:import';
    
    private static $ENCODE_MAP = ['utf-8' => 'UTF-8', 'utf8' => 'UTF-8',
        'shift-jis' => 'SJIS-win', 'sjis' => 'SJIS-win', 'euc-jp' => 'eucJP-win',
        'euc' => 'eucJP-win'];
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bank:import {encode} {file*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import bank';
    
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
     * 銀行データを保存.
     * @param array $bank_map 銀行データ
     * @return bool 成功の場合はtrueを、失敗の場合はfalseを返す
     */
    private function saveBankMap(array $bank_map) {
        $res = DB::transaction(function () use ($bank_map) {
            foreach ($bank_map as $bank_data) {
                $bank = $bank_data['bank'];
                
                // 既存の銀行情報を取得
                $cur_bank = Bank::where('version', '=', $bank->version)
                        ->where('code', '=', $bank->code)
                        ->first();
                
                if (isset($cur_bank->id)) {
                    // 既に登録されている場合
                    $bank_id = $cur_bank->id;
                } else {
                    // 銀行情報を保存
                    $bank->save();
                    $bank_id = $bank->id;
                }
                
                $branch_list = $bank_data['branch_list'];
                foreach ($branch_list as $bank_branch) {
                    // 既存の銀行支店情報を取得
                    $exist = BankBranch::where('version', '=', $bank_branch->version)
                            ->where('code', '=', $bank_branch->code)
                            ->where('bank_id', '=', $bank_id)
                            ->exists();
                    // 既に登録されている場合
                    if ($exist) {
                        continue;
                    }
                    
                    // 銀行支店情報を保存
                    $bank_branch->bank_id = $bank_id;
                    $bank_branch->save();
                }
            }
            
            return true;
        });
        
        return true;
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
        
        
        $encode = strtolower($this->argument('encode'));
        if (!isset(self::$ENCODE_MAP[$encode])) {
                $this->info('Encode is not supported.');
                return 1;
        }
        $enc = self::$ENCODE_MAP[$encode];
        
        $file_list = $this->argument('file');
        
        foreach ($file_list as $file_path) {
            // CSVファイルが存在しなかった場合
            if (!file_exists($file_path)) {
                $this->info(sprintf('Csv file not found.[%s]', $file_path));
                $this->info('falied');
                return 1;
            }
        }

        // バージョンを取得
        $next_version = max(Bank::getNextVersion(), BankBranch::getNextVersion());
        
        // 初期化
        Bank::initImport($next_version);
        BankBranch::initImport($next_version);
        
        foreach ($file_list as $file_path) {
            $n = 0;
            $bank_map = [];
            
            $file = new Csv\SplFileObject($file_path, 'r');
            $file->setEncode('UTF-8', $enc);
            while (true) {
                // パース
                if (($data = $file->fgetcsv()) === false || WrapPhp::count($data) < 6) {
                    break;
                }
                
                $bank_code = $data[0];
                if(!isset($bank_map[$bank_code])) {
                    $bank = new Bank();
                    $bank->code = $bank_code;
                    $bank->version = $next_version;
                    $bank->name = $data[3];
                    $bank->hurigana = $data[2];
                    
                    $bank_map[$bank_code] = ['bank' => $bank, 'branch_list' => []];
                }
                
                $bank_branch = new BankBranch();
                $bank_branch->code = $data[1];
                $bank_branch->version = $next_version;
                $bank_branch->name = $data[5];
                $bank_branch->hurigana = $data[4];
                
                $bank_map[$bank_code]['branch_list'][] = $bank_branch;

                $n = $n + 1;

                if ($n > 1000) {
                    // 一括保存
                    if (!$this->saveBankMap($bank_map)) {
                        // 失敗した場合
                        $this->error('Failed to save bank.[file:'.$file_path.']');
                        $this->error('falied');
                        return 1;
                    }

                    // リセット
                    $bank_map = [];
                    $n = 0;
                }
            }

            // 残ったデータを保存
            if (!empty($bank_map)) {
                // 一括保存
                if (!$this->saveBankMap($bank_map)) {
                    // 失敗した場合
                    $this->error('Failed to save bank.[file:'.$file_path.']');
                    $this->error('falied');
                    return 1;
                }
            }
        }
        
        // 終了
        Bank::endImport();
        BankBranch::endImport();
        
        //
        $this->info('success');
        
        return 0;
    }
}