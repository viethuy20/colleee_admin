<?php
namespace App\Console\UserPoint;

use App\Console\BaseCommand;
use App\Csv;
use App\User;
use App\UserPoint;
use WrapPhp;

class ImportSpProgram extends BaseCommand
{
    protected $tag = 'user_point:import_sp_program';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user_point:import_sp_program {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import sp_program user_point';

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
        
        $file_path = $this->argument('file');
        
        $file = new Csv\SplFileObject($file_path, 'r');
        while (true) {
            // パース
            if (($data = $file->fgetcsv()) === false || WrapPhp::count($data) < 5) {
                break;
            }
            $user_name = $data[0];
            $sp_program_id = $data[1];
            $diff_point = $data[2];
            $bonus_point = $data[3];
            $title = $data[4];
                        
            $user_id = User::getIdByName($user_name);
            
            // ユーザーIDがない場合
            if (empty($user_id)) {
                continue;
            }

            $user_point = UserPoint::getDefault(
                $user_id,
                $diff_point > 0 ? UserPoint::SP_PROGRAM_TYPE : UserPoint::SP_PROGRAM_WITH_REWARD_TYPE,
                $diff_point,
                $bonus_point,
                $title
            );
            $user_point->parent_id = $sp_program_id;
            $user_point->admin_id = 0;

            // トランザクション処理
            $user_point->addPoint(function () use ($user_point) {
                // 重複検証
                if (UserPoint::whereIn('type', [UserPoint::SP_PROGRAM_TYPE, UserPoint::SP_PROGRAM_WITH_REWARD_TYPE])
                        ->where('parent_id', '=', $user_point->parent_id)
                        ->where('user_id', '=', $user_point->user_id)
                        ->exists()) {
                    return false;
                }
                return true;
            });
        }
        
        // 成功
        $this->info('success');
        return 0;
    }
}
