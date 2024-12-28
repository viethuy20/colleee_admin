<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;

use App\ExchangeInfo;

class ExchangeInfoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ExchangeInfo::create([
            'type' => '18',
            'yen_rate' => '100',
            'messages' => '[]',
            'status' => '0',
            'start_at' => '2023-12-15 00:00:00',
            'stop_at' => '9999-12-31 23:59:59',
            'created_at' => '2023-12-15 00:00:00',
            'updated_at' => '2023-12-15 00:00:00',
        ]);
    }
}
