<?php
namespace App\Console\Partition;

use App\Console\BaseCommand;

class Report extends BaseCommand
{
    protected $tag = 'partition:report';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'partition:report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh report partition';

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
        if (!\App\Report::refreshPartition()) {
            $this->error('failed');
            return 1;
        }

        //
        $this->info('success');
        return 0;
    }
}
