<?php
namespace App\Console\UserPoint;

use Carbon\Carbon;

use App\Console\BaseCommand;
use App\User;
use App\UserPoint;

class Expire extends BaseCommand
{
    protected $tag = 'user_point:expire';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user_point:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire user_point';

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

        $expired_at_end = Carbon::now()->startOfMonth()->addMonth(-7)->endOfMonth();
        $admin_id = 0;

        $last_user_id = 1;
        while (true) {
            // ユーザーID一覧取得
            $user_id_list = User::where('status', '=' , User::COLLEEE_STATUS)
                ->where('id' , '>', $last_user_id)
                ->where('actioned_at', '<=', $expired_at_end)
                ->where('test' , '=', 0)
                ->orderBy('id', 'asc')
                ->take(5000)
                ->pluck('id')
                ->all();

            if (empty($user_id_list)) {
                break;
            }
            $last_user_id = max($user_id_list);

            // ポイント失効を実行
            UserPoint::expirePoint($admin_id, $user_id_list);
        }

        //
        $this->info('success');
        return 0;
    }
}
