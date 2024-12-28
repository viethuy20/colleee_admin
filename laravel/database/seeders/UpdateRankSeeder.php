<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;

class UpdateRankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('user_ranks')->where('rank', 1)->update(['rank' => 2]);
        DB::table('user_ranks')->where('rank', 4)->update(['rank' => 3]);
    }
}
