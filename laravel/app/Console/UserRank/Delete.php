<?php
namespace App\Console\UserRank;

use App\Console\BaseCommand;
use App\External\MountManager;
use App\UserRank;
use WrapPhp;

class Delete extends BaseCommand
{
    protected $tag = 'user_rank:delete';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user_rank:delete {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete user rank';

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
        // ZIP圧縮してファイルをマウント
        MountManager::zipMount(MountManager::BACKUP_USER_TYPE, $file_path);
        // 読み込みファイルを作成
        $file = new \App\Csv\SplFileObject($file_path, 'r');
        // ヘッダーを読み飛ばし
        $file->fgetcsv();
        $total = 0;
        while (true) {
            $user_rank_id_list = [];
            while (true) {
                // パース
                if (($data = $file->fgetcsv()) === false || WrapPhp::count($data) < 7 || $data[0] == '') {
                    break;
                }
                $user_rank_id_list[] = $data[0];

                if (WrapPhp::count($user_rank_id_list) >= 5000) {
                    break;
                }
            }

            //
            if (empty($user_rank_id_list)) {
                break;
            }
            $total += WrapPhp::count($user_rank_id_list);

            // ユーザーランク情報を削除
            UserRank::whereIn('id', $user_rank_id_list)
                ->delete();
            // 進捗
            $this->info(sprintf("Delete user ranks[%d]", $total));

            // 負荷対策で0.2秒スリープ
            usleep(200000);
        }

        // 読み込みファイルをクローズ
        $file = null;
        // ファイルクローズ待ちで0.2秒スリープ
        usleep(200000);

        //
        $this->info('success');
        return 0;
    }
}
