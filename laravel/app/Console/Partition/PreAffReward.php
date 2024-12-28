<?php
namespace App\Console\Partition;

use App\Console\BaseCommand;

class PreAffReward extends BaseCommand
{
    protected $tag = 'partition:pre_aff_reward';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'partition:pre_aff_reward';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh pre_aff_reward partition';

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

        // 最適化
        if (!\App\PreAffReward::refreshPartition()) {
            $this->error('failed');
            return 1;
        }

        //
        $this->info('success');
        return 0;
    }
}
