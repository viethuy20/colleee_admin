<?php
namespace App\Console\Test;

use App\Console\BaseCommand;

class Error extends BaseCommand
{
    protected $tag = 'test:error';
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:error';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test error';
    
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
        throw new \Exception('Test error');

        $this->info('success');
        return 0;
    }
}