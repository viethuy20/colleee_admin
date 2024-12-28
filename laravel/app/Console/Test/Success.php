<?php
namespace App\Console\Test;

use App\Console\BaseCommand;

class Success extends BaseCommand
{
    protected $tag = 'test:success';
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:success';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test success';
    
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
        
        //
        $this->info('success');
        return 0;
    }
}