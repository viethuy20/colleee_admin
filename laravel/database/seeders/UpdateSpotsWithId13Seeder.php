<?php
namespace Database\Seeders;
use App\Spot;
use Illuminate\Database\Seeder;

class UpdateSpotsWithId13Seeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            "banner_img_url"   => [
                "type"  => "img_url",
                "label" => "バナー画像",
            ],
            "banner_img_alt"   => [
                "type"  => "string",
                "label" => "バナー画像ALT",
            ],
            "header_img_url"   => [
                "type"  => "img_url",
                "label" => "ヘッダー画像",
            ],
            "header_img_alt"   => [
                "type"  => "string",
                "label" => "ヘッダー画像ALT",
            ],
            "header_img_ogp"   => [ // new
                "type"  => "img_url",
                "label" => "OGP画像",
            ],
            "detail"           => [
                "type"  => "string",
                "label" => "詳細",
            ],
            "rid"              => [
                "type"  => "number",
                "label" => "RID",
            ],
            "meta_title"       => [
                "type"  => "string",
                "label" => "タイトル（特集詳細画面）",
            ],
            "meta_keywords"    => [
                "type"  => "string",
                "label" => "キーワード（特集詳細画面）",
            ],
            "meta_description" => [
                "type"  => "string",
                "label" => "ディスクリプション（特集詳細画面）",
            ],
        ];
        $spot = Spot::find(Spot::SPOT_FEATURE_CATEGORY);
        if ($spot){
            $spot->update(['data' => json_encode($data)]);
        }
    }
}
