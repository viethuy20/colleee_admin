<?php
namespace App\Console\User;

use Carbon\Carbon;

use App\Console\BaseCommand;
use App\User;

class Refresh extends BaseCommand
{
    protected $tag = 'user:refresh';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh user';

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

        // 有効期限を取得
        $expired_at = Carbon::now()->copy()->startOfDay()->addDays(-31);
        // アクションのないユーザーのオートログインを無効化
        User::where('actioned_at', '<', $expired_at)
            ->whereNotNull('remember_token')
            ->update(['remember_token' => null]);

        //
        $this->info('success');

        return 0;
    }
}
