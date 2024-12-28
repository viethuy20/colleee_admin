<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;

class UpdateSpotsUseDevicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('spots')->where('id', '26')->update(['use_devices' => '1']);
    }
}
