<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use WrapPhp;

class AffRewardAction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aff_reward:action';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $today        = Carbon::today();
        $sixMonthsAgo = Carbon::now()->subMonths(6)->startOfMonth();
        $sixMonthsAgo = $sixMonthsAgo->toDateString() . ' 00:00:00';
        
        Log::info('today: ' . $today);
        Log::info('sixMonthsAgo: ' . $sixMonthsAgo);

        // $today        = '2022-02-08 00:00:00';
        // $sixMonthsAgo = '2021-08-01 00:00:00';

        $sql = "DELETE FROM aff_action_reports 
            WHERE report_day BETWEEN '$sixMonthsAgo' AND '$today'";
        DB::delete($sql);

        $sql = "
            SELECT	
                tmp.report_day AS report_day	
                , tmp.asp_id AS asp_id	
                , tmp.asp_name AS asp_name	
                , tmp.affiriate_id AS affiriate_id	
                , tmp.programs_id AS programs_id	
                , tmp.program_title AS program_title	
                , SUM(tmp.click) AS click	
                , SUM(tmp.cv) AS cv	
                , SUM(tmp.sum_point) AS sum_point	
                , SUM(tmp.point) AS point	
                , SUM(tmp.bonus_point) AS bonus_point	
            FROM	
                ( 	
                SELECT	
                DATE_FORMAT(external_links.created_at, '%Y-%m-%d') AS report_day	
                , external_links.asp_id AS asp_id	
                , asps.name AS asp_name	
                , affiriates.id AS affiriate_id	
                , external_links.program_id AS programs_id	
                , programs.title AS  program_title	
                , COUNT(external_links.program_id) AS click	
                , '' AS cv	
                , '' AS sum_point	
                , '' AS point 	
                , '' AS bonus_point	
            FROM	
                external_links 	
                JOIN asps 	
                ON asps.id = external_links.asp_id	
                JOIN programs	
                ON programs.id = external_links.program_id	
                JOIN affiriates	
                ON affiriates.asp_affiriate_id = external_links.asp_affiliate_id  	
            WHERE	
                external_links.created_at BETWEEN '$sixMonthsAgo' AND '$today'	
            GROUP BY	
                report_day	
                , asp_id	
                , affiriate_id	
                , programs_id	
            UNION	
            SELECT	
                DATE_FORMAT(aff_rewards.actioned_at, '%Y-%m-%d') AS report_day	
                , aff_rewards.asp_id AS asp_id	
                , asps.name AS asp_name	
                , aff_rewards.affiriate_id AS affiriate_id	
                , affiriates.parent_id AS programs_id	
                , aff_rewards.title AS  program_title	
                , '' AS click	
                , COUNT(aff_rewards.affiriate_id) AS cv	
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
                aff_rewards.status in (0,2,4) 
                    AND aff_rewards.actioned_at BETWEEN '$sixMonthsAgo' AND '$today'	
            GROUP BY	
                report_day	
                , asp_id	
                , affiriate_id	
                , program_title	
                ) AS tmp	
            GROUP BY	
                tmp.report_day	
                , tmp.asp_id	
                , tmp.asp_name	
                , tmp.affiriate_id	
                , tmp.programs_id	
                , tmp.program_title	
            ORDER BY	
                tmp.report_day	
                , tmp.asp_id	
                , tmp.programs_id	
      

        ";
        $data = DB::select($sql);
        if (WrapPhp::count($data) > 0) {
            $now = Carbon::now();
            foreach ($data as $item) {
                DB::table('aff_action_reports')->insert([
                    'report_day'    => $item->report_day,
                    'asp_id'        => $item->asp_id,
                    'asp_name'      => $item->asp_name,
                    'affiriate_id'  => $item->affiriate_id,
                    'program_id'    => $item->programs_id,
                    'program_title' => $item->program_title,
                    'click'         => $item->click,
                    'cv'            => $item->cv,
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
