<?php
namespace App\Console\Program;

use App\Console\BaseCommand;
use App\Services\GreeAdsRewardOfferService;

class GreeAdsRewardOffer extends BaseCommand
{
    protected $tag = 'program:gree_ads_rewards_offer';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'program:gree_ads_rewards_offer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh GreeAdsRewardOffer program';

    protected $greeAdsOfferService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(GreeAdsRewardOfferService $greeAdsOfferService)
    {
        $this->greeAdsOfferService = $greeAdsOfferService;
        
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

        $offerGreeAdsJson = $this->greeAdsOfferService->getOfferGreeAds();

        if (empty($offerGreeAdsJson)) {
            $this->error('network error!');
            return false;
        }

        if (!$offerGreeAdsJson['success']) {
            $this->error('get offer program failed!');
            return false;
        }
    
        $offerGreeAds = $offerGreeAdsJson['result'];
    
        $updateResult = $this->greeAdsOfferService->updateOfferPrograms($offerGreeAds);
        if (!$updateResult) {
            $this->error('update offer programs failed!');
            return false;
        }

        $this->info('success');
        return true;
    }

}
