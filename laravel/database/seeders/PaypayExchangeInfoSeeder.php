<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;

use App\ExchangeInfo;
use Illuminate\Support\Facades\DB;

class PaypayExchangeInfoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('exchange_infos')->insert([
            'type' => '19',
            'yen_rate' => '100',
            'messages' => '[]',
            'status' => '0',
            'start_at' => '2024-06-21 00:00:00',
            'stop_at' => '9999-12-31 23:59:59',
            'created_at' => '2024-06-21 00:00:00',
            'updated_at' => '2024-06-21 00:00:00',
        ]);
    }
}
