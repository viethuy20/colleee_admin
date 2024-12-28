<?php
namespace App\Console\Partition;

use App\Console\BaseCommand;

class EmailToken extends BaseCommand
{
    protected $tag = 'partition:email_token';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'partition:email_token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh email_token partition';

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
        if (!\App\EmailToken::refreshPartition()) {
            $this->error('failed');
            return 1;
        }

        //
        $this->info('success');
        return 0;
    }
}
