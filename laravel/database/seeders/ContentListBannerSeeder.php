<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;

class ContentListBannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Spot::create([
            'id'                                 => '22',
            'title'                              => 'ヘッダ上バナー（PC）',
            'use_img_ids'                        => '1',
            'use_devices'                        => '0',
            'default_devices'                    => '1',
            'data'                               => '{"url" : {"type" : "url", "label" : "URL"}, "img_url" : {"type" : "img_url", "label" : "画像"}}',
            'created_at'                         => Carbon\Carbon::now(),
            'updated_at'                         => Carbon\Carbon::now(),
        ]);

        \App\Spot::create([
            'id'                                 => '23',
            'title'                              => 'ヘッダ上バナー（SP）',
            'use_img_ids'                        => '1',
            'use_devices'                        => '0',
            'default_devices'                    => '6',
            'data'                               => '{"url" : {"type" : "url", "label" : "URL"}, "img_url" : {"type" : "img_url", "label" : "画像"}}',
            'created_at'                         => Carbon\Carbon::now(),
            'updated_at'                         => Carbon\Carbon::now(),
        ]);

        \App\Spot::create([
            'id'                                 => '24',
            'title'                              => 'ミニバナー（PC）',
            'use_img_ids'                        => '1',
            'use_devices'                        => '0',
            'default_devices'                    => '1',
            'data'                               => '{"url" : {"type" : "url", "label" : "URL"}, "img_url" : {"type" : "img_url", "label" : "画像"}}',
            'created_at'                         => Carbon\Carbon::now(),
            'updated_at'                         => Carbon\Carbon::now(),
        ]);

        \App\Spot::create([
            'id'                                 => '25',
            'title'                              => 'ミニバナー（SP）',
            'use_img_ids'                        => '1',
            'use_devices'                        => '0',
            'default_devices'                    => '6',
            'data'                               => '{"url" : {"type" : "url", "label" : "URL"}, "img_url" : {"type" : "img_url", "label" : "画像"}}',
            'created_at'                         => Carbon\Carbon::now(),
            'updated_at'                         => Carbon\Carbon::now(),
        ]);

        \App\Spot::create([
            'id'                                 => '26',
            'title'                              => 'ランキング下バナー',
            'use_img_ids'                        => '1',
            'use_devices'                        => '0',
            'default_devices'                    => '7',
            'data'                               => '{"url" : {"type" : "url", "label" : "URL"}, "img_url" : {"type" : "img_url", "label" : "画像"}}',
            'created_at'                         => Carbon\Carbon::now(),
            'updated_at'                         => Carbon\Carbon::now(),
        ]);
    }
}
