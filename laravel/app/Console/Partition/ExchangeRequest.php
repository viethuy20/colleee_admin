<?php
namespace App\Console\Partition;

use App\Console\BaseCommand;

class ExchangeRequest extends BaseCommand
{
    protected $tag = 'partition:exchange_request';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'partition:exchange_request';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh exchange_request partition';

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
        if (!\App\ExchangeRequest::refreshPartition()) {
            $this->error('failed');
            return 1;
        }

        //
        $this->info('success');
        return 0;
    }
}
