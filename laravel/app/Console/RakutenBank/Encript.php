<?php
namespace App\Console\RakutenBank;


use Illuminate\Console\Command;

use App\External\RakutenBank;

/**
 * Description of Encript
 *
 * @author t_moriizumi
 */
class Encript extends Command {
    protected $tag = 'rakuten_bank:encript';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rakuten_bank:encript {data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Encript rakuten_bank';
    
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
        //
        $this->info(RakutenBank::encript($this->argument('data')));
        
        return 0;
    }
}
