<?php
namespace App\Services;

use App\External\SkyFlagOffer;
use App\OfferCvPoint;
use App\OfferProgram;
use App\OfferProgramCategories;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use WrapPhp;

/**
 * SkyFlagOfferService.
 * @author y_oba
 */
class SkyFlagOfferService
{

    // 広告情報をAPIで取得
    public function getOfferSkyFlag($osType)
    {
        return SkyFlagOffer::get($osType);
    }

    public function updateOfferPrograms(array $offerSkyFlagAds, $platformType)
    {
        $offerPrograms = $this->getOfferPrograms($platformType);
    
        DB::beginTransaction();
        try {
            foreach ($offerSkyFlagAds as $offerSkyFlagAd) {
                $offerProgram = $this->findOfferProgram($offerPrograms, $offerSkyFlagAd);
                $this->updateOfferProgram($offerProgram, $offerSkyFlagAd, $platformType);
                
                foreach ($offerSkyFlagAd["conversionPoints"] as $cvPoint) {
                    $this->updateOfferCvPoint($offerProgram, $offerSkyFlagAd, $cvPoint);
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
    private function getOfferPrograms($platformType)
    {
        return OfferProgram::where('asp_id', SkyFlagOffer::ASP_ID)
                    ->where('platform_type', SkyFlagOffer::$PLATFORM_MAP[$platformType])
                    ->with('offerCvPoints')
                    ->get()
                    ->keyBy('ad_id')
                    ->all();
    }

    //　広告情報を取得
    private function findOfferProgram($offerPrograms, $offerSkyFlagAd)
    {
        $adId = $offerSkyFlagAd['id'];
        if (isset($offerPrograms[$adId])) {
            return $offerPrograms[$adId];
        } else {
            $offerProgram = new OfferProgram();
            $offerProgram->asp_id = SkyFlagOffer::ASP_ID;    
            $offerProgram->ad_id = $adId;
            return $offerProgram;
        }
    }

    // 広告情報を更新
    private function updateOfferProgram($offerProgram, $offerSkyFlagAd, $platformType)
    {
        $offerProgram->title = $offerSkyFlagAd['name'];
        $offerProgram->app_id = $offerSkyFlagAd['appId'];
            
        if (isset(SkyFlagOffer::$PLATFORM_MAP[$platformType])){
            $offerProgram->platform_type = SkyFlagOffer::$PLATFORM_MAP[$platformType];
        }

        $offerProgram->multi_course = WrapPhp::count($offerSkyFlagAd['conversionPoints']) > 1;
        $offerProgram->publish_start_at = $offerSkyFlagAd['publishStartAt'] ?? null;
        $offerProgram->publish_end_at = $offerSkyFlagAd['publishEndAt'] ?? null;
        $offerProgram->revenue_type = OfferProgram::REVENUE_AMOUNT;
        $offerProgram->save();

        $this->updateOfferProgramCategories($offerProgram, $offerSkyFlagAd);
    }

    // 広告カテゴリ情報を更新
    private function updateOfferProgramCategories($offerProgram, $offerSkyFlagAd)
    {
        OfferProgramCategories::where('offer_program_id', $offerProgram->id)->delete();

        $offerProgramCategories = [];
        foreach ($offerSkyFlagAd['offerCategories'] as $category) {
            $offerProgramCategories[] = [
                'category_id' => $category['id'],
                'category_name' => SkyFlagOffer::$CAMPAIGN_SUB_CATEGORY_MAP[$category['id']] ?? null,
            ];
        }
        $offerProgram->offerProgramCategories()->createMany($offerProgramCategories);
    }

    // 成果地点情報を更新
    private function updateOfferCvPoint($offerProgram, $offerSkyFlagAd, $cvPoint)
    {
        // 最終ステップの場合コースIDは連携されないためnullで対応
        $aff_course_id = $cvPoint['step'] == WrapPhp::count($offerSkyFlagAd['conversionPoints']) ? null : $cvPoint['step'];
        
        $offerCvPoint = $offerProgram->offerCvPoints->firstWhere('aff_course_id', $aff_course_id);
        if (empty($offerCvPoint)) {
            $offerCvPoint = new OfferCvPoint();
            $offerCvPoint->aff_course_id = $aff_course_id;
            $offerProgram->offerCvPoints()->save($offerCvPoint);
        }

        $offerCvPoint->course_name = $cvPoint['name'];
        $offerCvPoint->point = $cvPoint['actualPoint'] ?? null;
        $offerCvPoint->point_rate = null;
        
        if ($cvPoint['priceUnit'] == 1) {
            $offerCvPoint->revenue = $cvPoint['priceValue'] ?? null;
            $offerCvPoint->revenue_rate = null;    
        } 
        else if ($cvPoint['priceUnit'] == 2) {
            $offerCvPoint->revenue = null;
            $offerCvPoint->revenue_rate = $cvPoint['priceValue'] ?? null;
        }

        $offerCvPoint->category_id = $offerSkyFlagAd['cvCategoryType'] ?? null;
        $offerCvPoint->category_name = SkyFlagOffer::$THANKS_CATEGORY_MAP[$offerSkyFlagAd['cvCategoryType']] ?? null;
        $offerCvPoint->save();
    }

    // 1年以上更新されていない広告情報を削除
    public function deleteOfferPrograms($platformType)
    {
        $expiredDate = Carbon::now()->subYear();
        $offerProgramIds = OfferProgram::where('asp_id', '=', SkyFlagOffer::ASP_ID)
                                ->where('platform_type', SkyFlagOffer::$PLATFORM_MAP[$platformType])
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
