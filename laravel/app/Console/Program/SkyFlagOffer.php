<?php

namespace App\Console\Program;

use App\Console\BaseCommand;
use App\Services\SkyFlagOfferService;

class SkyFlagOffer extends BaseCommand
{
    protected $tag = 'program:sky_flag_offer';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'program:sky_flag_offer {--platform_type= : The platform type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh SkyFlagOffer program';

    protected $skyFlagOfferService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(SkyFlagOfferService $skyFlagOfferService)
    {
        $this->skyFlagOfferService = $skyFlagOfferService;
        
        parent::__construct();
    }
 
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $platformOption = (int) $this->option('platform_type');

        $this->info("start --platform_type={$platformOption}");

        $offerSkyFlagJson = $this->skyFlagOfferService->getOfferSkyFlag($platformOption);

        if (empty($offerSkyFlagJson)) {
            $this->error('network error!');
            return false;
        }

        if (isset($offerSkyFlagJson['Message'])) {
            $this->error("get offer program failed!: {$offerSkyFlagJson['Message']}");
            return false;
        }

        $updateResult = $this->skyFlagOfferService->updateOfferPrograms($offerSkyFlagJson, $platformOption);
        if (!$updateResult) {
            $this->error('update offer programs failed!');
            return false;
        }

        $this->info('success');
        return true;
    }
}
