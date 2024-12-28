<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use WrapPhp;

class AffRewardReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aff_reward:report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aff reward report';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        $today     = Carbon::today();
        $yesterday = Carbon::yesterday();
        Log::info('today: ' . $today);
        Log::info('yesterday: ' . $yesterday);

        // $today     = '2022-01-02 00:00:00';
        // $yesterday = '2022-01-01 00:00:00';

        DB::delete("DELETE FROM aff_confirm_reports
            WHERE report_day BETWEEN :yesterday AND :today ", [
            ':yesterday' => $yesterday,
            ':today'     => $today,
        ]);

        $data = DB::select("
                    SELECT
                        DATE_FORMAT(aff_rewards.confirmed_at, '%Y-%m-%d') AS report_day
                        , aff_rewards.asp_id AS asp_id
                        , asps.name AS asp_name
                        , aff_rewards.affiriate_id AS affiriate_id
                        , affiriates.parent_id AS programs_id
                        , aff_rewards.title AS  program_title
                        , COUNT(aff_rewards.point) AS confirm_count
                        , SUM(aff_rewards.point) AS sum_point
                        , SUM(aff_rewards.diff_point) AS point
                        , SUM(aff_rewards.bonus_point ) AS bonus_point
                    FROM
                        aff_rewards
                        JOIN asps
                        ON asps.id = aff_rewards.asp_id
                        JOIN affiriates
                        ON affiriates.id = aff_rewards.affiriate_id
                    WHERE
                        aff_rewards.status = 0 AND aff_rewards.confirmed_at BETWEEN :yesterday AND :today
                    GROUP BY
                        report_day
                        , asp_id
                        , affiriate_id
                        , program_title
                    ORDER BY
                        report_day;

            ", [
            ':yesterday' => $yesterday,
            ':today'     => $today,
        ]);

        if (WrapPhp::count($data) > 0) {
            $now = Carbon::now();
            foreach ($data as $item) {
                DB::table('aff_confirm_reports')->insert([
                    'report_day'    => $item->report_day,
                    'asp_id'        => $item->asp_id,
                    'asp_name'      => $item->asp_name,
                    'affiriate_id'  => $item->affiriate_id,
                    'program_id'    => $item->programs_id,
                    'program_title' => $item->program_title,
                    'confirm_count' => $item->confirm_count,
                    'sum_point'     => $item->sum_point,
                    'point'         => $item->point,
                    'bonus_point'   => $item->bonus_point,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
            }
        }

    }
}
