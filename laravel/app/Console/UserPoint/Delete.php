<?php
namespace App\Console\UserPoint;

use DB;

use App\Console\BaseCommand;
use App\External\MountManager;
use App\User;
use App\UserPoint;
use WrapPhp;

class Delete extends BaseCommand
{
    const TITLE = 'ポイント保持';

    protected $tag = 'user_point:delete';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user_point:delete {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete user_point';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    private $user_point_total = 0;

    /**
     * 調整用ポイント履歴を発行.
     * @param SplFileObject $file CSVファイル
     * @return bool 成功の場合はtrueを、失敗の場合はfalseを返す
     */
    private function addAdjustUserPoint($file) : bool
    {
        $admin_id = 0;

        $file->rewind();
        $file->fgetcsv();

        $this->user_point_total = 0;
        $user_point_id_map = [];
        while (true) {
            // パース
            if (($data = $file->fgetcsv()) === false || WrapPhp::count($data) < 12 || $data[0] == '') {
                break;
            }
            $user_point_id = $data[0];
            $user_id = $data[1];

            $user_point_id_map[$user_id] = max(($user_point_id_map[$user_id] ?? 0), $user_point_id);
            $this->user_point_total = $this->user_point_total + 1;
        }

        // 履歴が存在しない場合
        if (empty($user_point_id_map)) {
            return true;
        }

        $n = WrapPhp::count($user_point_id_map);
        $user_point_id_map_list = array_chunk($user_point_id_map, 5000, true);

        $i = 0;
        foreach ($user_point_id_map_list as $user_point_id_map) {
            $i = $i + WrapPhp::count($user_point_id_map);
            $this->info(sprintf("Add adjust user_points[%d/%d]", $i, $n));

            $user_id_list = array_keys($user_point_id_map);

            // 有効なユーザーID一覧を取得
            $exist_user_id_list = User::whereIn('id', $user_id_list)
                ->whereIn('status', [User::COLLEEE_STATUS, User::LOCK1_STATUS, User::LOCK2_STATUS])
                ->pluck('id')
                ->all();
            // ユーザーの最新のポイント履歴を取得
            $last_user_point_map = UserPoint::getUserPointMap($user_id_list);

            // 調整用ポイントを発行
            foreach ($last_user_point_map as $user_id => $cur_user_point) {
                // 最新のポイント履歴を削除しない場合、調整ポイントは発行しない
                if ($cur_user_point->id > $user_point_id_map[$user_id]) {
                    continue;
                }
                // 正規ではないユーザーがポイントを持っていない場合
                if (!in_array($user_id, $exist_user_id_list, true) && $cur_user_point->point == 0 &&
                    !UserPoint::where('user_id', '=', $user_id)
                        ->where('point', '>', 0)
                        ->exists()) {
                    continue;
                }
                // 調整用ポイントを作成
                $user_point = UserPoint::getDefault($user_id, UserPoint::ADMIN_TYPE, 0, 0, self::TITLE);
                // 調整用ポイント発行に失敗した場合
                if (!$user_point->addPoint()) {
                    $this->error(sprintf("Failed add point.[user_id:%d]", $user_id));
                    return false;
                }
                // 負荷対策で0.1秒スリープ
                usleep(100000);
            }
        }

        return true;
    }

    /**
     * 履歴を削除する.
     * @param SplFileObject $file CSVファイル
     * @return bool 成功の場合はtrueを、失敗の場合はfalseを返す
     */
    private function deleteUserPointList($file) : bool
    {
        $file->rewind();
        $file->fgetcsv();

        $i = 0;
        while (true) {
            $delete_user_point_id_list = [];
            while (true) {
                // パース
                if (($data = $file->fgetcsv()) === false || WrapPhp::count($data) < 12 || $data[0] == '') {
                    break;
                }
                $user_point_id = $data[0];
                $i = $i + 1;

                $delete_user_point_id_list[] = $user_point_id;
                if (WrapPhp::count($delete_user_point_id_list) >= 5000) {
                    break;
                }
            }

            if (empty($delete_user_point_id_list)) {
                break;
            }

            // 削除実行
            UserPoint::whereIn('id', $delete_user_point_id_list)
                ->delete();
            
            $this->info(sprintf("Delete user_points[%d/%d]", $i, $this->user_point_total));

            // 負荷対策で0.2秒スリープ
            usleep(200000);
        }

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

        $file_path = $this->argument('file');
        // ZIP圧縮してファイルをマウント
        MountManager::zipMount(MountManager::BACKUP_USER_POINT_TYPE, $file_path);

        $file = new \App\Csv\SplFileObject($file_path, 'r');

        // 調整用ポイント履歴を発行
        if (!$this->addAdjustUserPoint($file)) {
            $this->error('failed');
            return 1;
        }
        // ユーザーポイント履歴を削除
        if (!$this->deleteUserPointList($file)) {
            $this->error('failed');
            return 1;
        }
        //
        $this->info('success');
        return 0;
    }
}
