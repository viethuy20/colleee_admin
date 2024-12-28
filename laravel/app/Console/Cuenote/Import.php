<?php
namespace App\Console\Cuenote;

use Carbon\Carbon;

use App\Console\BaseCommand;
use App\User;
use App\UserPoint;
use App\UserRank;

//use App\External\CuenoteFC;

/**
 * Description of Import
 *
 * @author t_moriizumi
 */
class Import extends BaseCommand
{
    protected $tag = 'cuenote:import';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cuenote:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import cuenote';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        ini_set('memory_limit', '512M');
    }
    
    public static function getFile(string $file_path)
    {
        // 書き込みファイルを開く
        $file = new \App\Csv\SplFileObject($file_path, 'w');
        return $file;
    }

    private static function writeCsv($file1, $file2, $file3, $user_list, Carbon $now)
    {
        $now_time = $now->format('Y-m-d H:i:s');

        $end_at = $now->copy()
            ->startOfMonth()
            ->addMonths(-12)
            ->addSeconds(-1);

        //
        $file2_status_list = [User::COLLEEE_STATUS, User::LOCK1_STATUS,];

        $user_id_list = $user_list->pluck('id')->all();

        $user_rank_map = UserRank::getUserRankMap($user_id_list);
        /*
        $user_point_map = UserPoint::getUserPointMap(
            $user_id_list,
            $now->copy()->addDays(-1)->endOfDay()
        );
        */
        $up_point_map = UserPoint::getUpPointMap(
            $user_id_list,
            $now->copy()->addDays(-1)
        );

        foreach ($user_list as $user) {
            $user_rank = $user_rank_map[$user->id] ?? 0;
            //$user_point = ($user_point_map[$user->id])->point ?? 0;
            $user_point = $user->point;
            $up_point = $up_point_map[$user->id] ?? 0;
            //
            $data = [
                $user->email, $user->name, $user->nickname ?? '', $user_rank,
                $user_point, $up_point, $user->sex, $user->birthday->format('md'),
                $user->prefecture_id, $user->created_at->format('Y-m-d H:i:s'),
                $user->email_magazine, $user->promotion_id, $user->point_expire_at->format('Y-m-d H:i:s'),
                $now_time,
            ];

            $line = '"'.implode('","', $data)."\"\r\n";
           
            // 全ユーザー
            if ($user->email_status == 0) {
                $file1->fwrite($line);

                // メルマガ
                if (in_array($user->status, $file2_status_list) && $user->email_magazine == 1) {
                    $file2->fwrite($line);
                }
            }

            // 退会警告
            if (isset($file3) && $user->status == User::COLLEEE_STATUS && $user->actioned_at->lt($end_at)) {
                $file3->fwrite($line);
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

        $now = Carbon::now();

        // ディレクトリ
        $base_path = config('path.cuenote').DIRECTORY_SEPARATOR.$now->format('YmdHis').$now->micro;

        // 全配信ファイルパス作成
        $file_path1 = $base_path.'_2.csv';
        // メルマガ配信ファイルパス作成
        $file_path2 = $base_path.'_email_magazine_2.csv';
        // 退会警告ファイルパス作成
        $file_path3 = in_array($now->day, [2, 10], true) ? $base_path.'_nonaction.csv' : null;
        // 出力先ファイル作成
        $file1 = self::getFile($file_path1);
        $file2 = self::getFile($file_path2);
        $file3 = isset($file_path3) ? self::getFile($file_path3) : null;

        $user_id = 0;
        while (true) {
            // ユーザーを10,000件取得
            $user_list = User::select(
                'id',
                'email',
                'nickname',
                'sex',
                'birthday',
                'prefecture_id',
                'created_at',
                'email_magazine',
                'promotion_id',
                'actioned_at'
            )
                ->whereIn('status', [User::COLLEEE_STATUS, User::LOCK1_STATUS, User::LOCK2_STATUS])
                ->where('id', '>', $user_id)
                ->orderBy('id', 'asc')
                ->take(10000)
                ->get();

            //　なくなったら終了
            if ($user_list->isEmpty()) {
                break;
            }

            // CSV書き込み
            self::writeCsv($file1, $file2, $file3, $user_list, $now);
            // 最後のユーザーIDを取得
            $user_id = $user_list->last()->id;
            $user_list = null;
        }
        // 全ユーザー
        $file1 = null;
        zip([$file_path1], $file_path1.'.zip');
        @unlink($file_path1);
        // メルマガ
        $file2 = null;
        zip([$file_path2], $file_path2.'.zip');
        @unlink($file_path2);
        // 退会警告
        if (isset($file3)) {
            $file3 = null;
            zip([$file_path3], $file_path3.'.zip');
            @unlink($file_path3);
        }

        /*
        // CuenoteFCオブジェクト作成
        $cuenote_fc = CuenoteFC::import(CuenoteFC::getAdBookId('all'), 'replace', 'noheader', 'none', $file_path);

        // 実行
        $res = $cuenote_fc->execute();

        // 失敗した場合
        if (!$res) {
            // エラーコードを出力
            $this->info(sprintf("error.[status:%s,code:%s]", $cuenote_fc->getStatus(), $cuenote_fc->getbody()));
            return 1;
        }
         */

        //
        $this->info('success');
        return 0;
    }
}
