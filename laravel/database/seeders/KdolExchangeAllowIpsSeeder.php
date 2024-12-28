<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;

use App\ExchangeInfo;
use Illuminate\Support\Facades\DB;

class KdolExchangeAllowIpsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('exchange_allow_ips')->insert([
            'type' => '22',
            'allow_ips' => '52.195.253.68',
            'created_at' => '2024-10-11 00:00:00',
            'updated_at' => '2024-10-11 00:00:00',
        ]);
    }
}
