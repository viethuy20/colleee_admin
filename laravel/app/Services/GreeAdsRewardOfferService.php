<?php
namespace App\Services;

use App\External\GreeAdsRewardOffer;
use App\OfferCvPoint;
use App\OfferProgram;
use App\OfferProgramCategories;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * GreeAdsRewardOfferService.
 * @author y_oba
 */
class GreeAdsRewardOfferService
{
    // 広告情報をAPIで取得
    public function getOfferGreeAds()
    {
        return GreeAdsRewardOffer::get();
    }

    public function updateOfferPrograms(array $offerGreeAds)
    {
        $offerPrograms = $this->getOfferPrograms();
    
        DB::beginTransaction();
        try {
            foreach ($offerGreeAds as $offerGreeAd) {
                $offerProgram = $this->findOfferProgram($offerPrograms, $offerGreeAd);
    
                $this->updateOfferProgram($offerProgram, $offerGreeAd);
    
                foreach ($offerGreeAd["thanks"] as $thanks) {
                    $this->updateOfferCvPoint($offerProgram, $thanks);
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error($e);
            return false;
        }
    
        return true;
    }

    // 登録済み広告情報一覧を取得
    private function getOfferPrograms()
    {
        return OfferProgram::where('asp_id', GreeAdsRewardOffer::ASP_ID)
                    ->with('offerCvPoints')
                    ->get()
                    ->keyBy('ad_id')
                    ->all();
    }

    // 広告情報を取得
    private function findOfferProgram($offerPrograms, $offerGreeAd)
    {
        $adId = $offerGreeAd['campaign_id'];
        if (isset($offerPrograms[$adId])) {
            return $offerPrograms[$adId];
        } else {
            $offerProgram = new OfferProgram();
            $offerProgram->asp_id = GreeAdsRewardOffer::ASP_ID;    
            $offerProgram->ad_id = $adId;
            return $offerProgram;
        }
    }

    // 広告情報を更新
    private function updateOfferProgram($offerProgram, $offerGreeAd)
    {
        $offerProgram->title = $offerGreeAd['site_name'];
        $offerProgram->app_id = $offerGreeAd['market_app_id'];
        $offerProgram->platform_type = GreeAdsRewardOffer::$PLATFORM_MAP[$offerGreeAd['platform_id']];
        $offerProgram->multi_course = $offerGreeAd['is_multi_mission'];
        $offerProgram->publish_start_at = $offerGreeAd['start_time'] ?? null;
        $offerProgram->publish_end_at = $offerGreeAd['end_time'] ?? null;
        $offerProgram->revenue_type = $offerGreeAd['campaign_revenue_type'] ?? null;
        $offerProgram->save();

        $this->updateOfferProgramCategories($offerProgram, $offerGreeAd);
    }

    // 広告カテゴリ情報を更新
    private function updateOfferProgramCategories($offerProgram, $offerGreeAd)
    {
        OfferProgramCategories::where('offer_program_id', $offerProgram->id)->delete();

        $offerProgramCategories = [];
        foreach ($offerGreeAd['campaign_sub_category'] as $category) {
            $offerProgramCategories[] = [
                'category_id' => $category,
                'category_name' => GreeAdsRewardOffer::$CAMPAIGN_SUB_CATEGORY_MAP[$category] ?? null,
            ];
        }
        $offerProgram->offerProgramCategories()->createMany($offerProgramCategories);
    }

    // 成果地点情報を更新
    private function updateOfferCvPoint($offerProgram, $thanks)
    {
        $offerCvPoint = $offerProgram->offerCvPoints->firstWhere('aff_course_id', $thanks['advertisement_id'] ?? null);
        if (empty($offerCvPoint)) {
            $offerCvPoint = new OfferCvPoint();
            $offerCvPoint->aff_course_id = $thanks['advertisement_id'] ?? null;
            $offerProgram->offerCvPoints()->save($offerCvPoint);
        }

        $offerCvPoint->course_name = $thanks['thanks_name'];
        $offerCvPoint->point = $thanks['thanks_point'] ?? null;
        $offerCvPoint->point_rate = $thanks['thanks_point_rate'] ?? null;
        $offerCvPoint->revenue = $thanks['media_revenue'] ?? null;
        $offerCvPoint->revenue_rate = $thanks['media_revenue_rate'] ?? null;
        $offerCvPoint->category_id = $thanks['thanks_category'] ?? null;
        $offerCvPoint->category_name = GreeAdsRewardOffer::$THANKS_CATEGORY_MAP[$thanks['thanks_category']] ?? null;
        $offerCvPoint->save();
    }

    // 1年以上更新されていない広告情報を削除
    public function deleteOfferPrograms()
    {
        $expiredDate = Carbon::now()->subYear();
        $offerProgramIds = OfferProgram::where('asp_id', '=', GreeAdsRewardOffer::ASP_ID)
                                ->where('updated_at', '<', $expiredDate)
                                ->pluck('id')
                                ->toArray();
    
        if (!empty($offerProgramIds)) {
            try {
                DB::transaction(function () use ($offerProgramIds) {
                    OfferProgram::whereIn('id', $offerProgramIds)->delete();
                    OfferProgramCategories::whereIn('offer_program_id', $offerProgramIds)->delete();
                    OfferCvPoint::whereIn('offer_program_id', $offerProgramIds)->delete();
                });
            } catch (\Exception $e) {
                \Log::error($e);
                return false;
            }
        }
    
        return true;
    }
}
?>