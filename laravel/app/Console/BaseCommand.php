<?php
namespace App\Console;

use Illuminate\Console\Command;

class BaseCommand extends Command
{
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        set_time_limit(0);
        ini_set('memory_limit', '512M');
    }
    
    protected $tag = 'base_command';
    public function line($string, $style = null, $verbosity = null)
    {
        parent::line('['.date('Y-m-d H:i:s', time()).'] '.$this->tag.' '.$string, $style, $verbosity);
    }
}
