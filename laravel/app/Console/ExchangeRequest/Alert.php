<?php

namespace App\Console\ExchangeRequest;

use App\Console\BaseCommand;
use App\ExchangeRequest;
use Carbon\Carbon;
use WrapPhp;

class Alert extends BaseCommand
{
    protected $tag = 'exchange_request:alert';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exchange_request:alert';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'exchange_request:alert';

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
        $this->info('start');

        $today = Carbon::now();
        $basetime = $today->copy()->addHour(-1);

        $message_list = [];

        $paymentgatway_count = ExchangeRequest::ofBank()
        ->ofRollBack()
        ->where('scheduled_at', '>=', $basetime)
        ->where('response_code', '=', 'BA1540071')
        ->count();

        if ($paymentgatway_count > 0) $message_list['PaymentGateway'] = $paymentgatway_count;
        
        $nttcard_count = ExchangeRequest::ofNttCardGiftCode()
        ->ofRollBack()
        ->where('scheduled_at', '>=', $basetime)
        ->where('response_code', '=', '0300')
        ->count();

        if ($nttcard_count > 0) $message_list['NttCard'] = $nttcard_count;

        $voyage_count = ExchangeRequest::ofVoyageGiftCode()
        ->ofRollBack()
        ->where('scheduled_at', '>=', $basetime)
        ->where('response_code', '=', '03')
        ->count();

        if ($voyage_count > 0) $message_list['Voyage'] = $voyage_count;

        $dotmany_count = ExchangeRequest::ofDotMoney()
        ->ofRollBack()
        ->where('scheduled_at', '>=', $basetime)
        ->where('response_code', '=', 'business.request_id_already_exist')
       ->count();

        if ($dotmany_count > 0) $message_list['DotManey'] = $dotmany_count;

        if (WrapPhp::count($message_list) > 0) { 
            try {
                $options = ['datetime' => $basetime, 'message_list' => $message_list];
                $mailable = new \App\Mail\ExchangeAlert('exchange_alert', $options);
                \Mail::send($mailable);
            } catch (\Exception $e) {
                \Log::error($e->getTraceAsString());
                $this->info('failed');
                return 1;
            }
    
        } 

        $this->info('success');

        return 0;
    }
}
