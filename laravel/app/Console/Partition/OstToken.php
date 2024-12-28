<?php
namespace App\Console\Partition;

use App\Console\BaseCommand;

class OstToken extends BaseCommand
{
    protected $tag = 'partition:ost_token';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'partition:ost_token';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh ost_token partition';
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
        if (!\App\OstToken::refreshPartition()) {
            $this->error('OstToken failed');
            return 1;
        }

        //
        $this->info('success');
        return 0;
    }
}
