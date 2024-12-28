<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use WrapPhp;

class PointMonthlyReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'point_month:report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Save point monthly reports';

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
        $firstDayOfLastMonth = new \DateTime("first day of last month");
        $firstDayOfLastMonth = $firstDayOfLastMonth->format('Y-m-d 00:00:00');

        $lastDayOfLastMonth = new \DateTime("last day of last month");
        $lastDayOfLastMonth = $lastDayOfLastMonth->format('Y-m-d 23:59:59');

        $sql = "DELETE FROM point_monthly_reports
            WHERE report_day = '$firstDayOfLastMonth'";
        DB::delete($sql);

        $sql = "SELECT
        tmp.report_day
        , SUM(tmp.sum_balance_point) AS sum_balance_point
        , SUM(tmp.sum_action_point) + SUM(sum_confirm_point) AS sum_action_point
        , SUM(tmp.sum_confirm_point) AS sum_confirm_point
        , SUM(tmp.sum_exchange_point) AS sum_exchange_point
        , SUM(tmp.sum_lost_point) AS sum_lost_point
      FROM
        (
          (
            SELECT
              DATE_FORMAT('$lastDayOfLastMonth', '%Y-%m-01') AS report_day
              , SUM(point) AS sum_balance_point
              , 0 AS sum_action_point
              , 0 AS sum_confirm_point
              , 0 AS sum_exchange_point
              , 0 AS sum_lost_point
            FROM
              user_points
            WHERE
              (id, user_id) IN (
                SELECT
                  MAX(id)
                  , user_id
                FROM
                  user_points
                WHERE
                  created_at <= '$lastDayOfLastMonth'
                GROUP BY
                  user_id
              )
          )
          UNION (
            SELECT
              DATE_FORMAT(aff_rewards.actioned_at, '%Y-%m-01') AS report_day
              , 0 AS sum_balance_point
              , SUM(aff_rewards.point) AS sum_action_point
              , 0 AS sum_confirm_point
              , 0 AS sum_exchange_point
              , 0 AS sum_lost_point
            FROM
              aff_rewards
            WHERE
              aff_rewards.status in (2, 4)
              AND aff_rewards.actioned_at BETWEEN '$firstDayOfLastMonth' AND '$lastDayOfLastMonth'
            GROUP BY
              report_day
            ORDER BY
              report_day
          )
          UNION (
            SELECT
              DATE_FORMAT(user_points.created_at, '%Y-%m-01') AS report_day
              , 0 AS sum_balance_point
              , 0 AS sum_action_point
              , SUM(
                CASE
                  WHEN user_points.type = 7
                    THEN bonus_point
                  ELSE diff_point + bonus_point
                  END
              ) AS sum_confirm_point
              , 0 AS sum_exchange_point
              , SUM(
                CASE
                  WHEN user_points.type = 7
                    THEN diff_point
                  ELSE 0
                  END
              ) * - 1 AS sum_lost_point
            FROM
              user_points
            WHERE
              user_points.created_at BETWEEN '$firstDayOfLastMonth' AND '$lastDayOfLastMonth'
              AND user_points.type IN (2, 3, 4, 6, 7, 13, 14, 15, 20, 21, 22, 23)
            GROUP BY
              report_day
          )
          UNION (
            SELECT
              DATE_FORMAT(exchange_requests.created_at, '%Y-%m-01') AS report_day
              , 0 AS sum_balance_point
              , 0 AS sum_action_point
              , 0 AS sum_confirm_point
              , SUM(exchange_requests.point) AS sum_exchange_point
              , 0 AS sum_lost_point
            FROM
              exchange_requests
            WHERE
              exchange_requests.created_at BETWEEN '$firstDayOfLastMonth' AND '$lastDayOfLastMonth'
              AND exchange_requests.type IN (1, 5, 8, 9, 10, 11, 12, 13, 14)
              AND status IN (0, 2)
            GROUP BY
              report_day
          )
        ) AS tmp
      GROUP BY
        tmp.report_day
      ";
        $data = DB::select($sql);
        if (WrapPhp::count($data) > 0) {
            $now = Carbon::now();
            foreach ($data as $item) {
                DB::table('point_monthly_reports')->insert([
                    'report_day'         => $item->report_day,
                    'sum_balance_point'  => $item->sum_balance_point,
                    'sum_action_point'   => $item->sum_action_point,
                    'sum_confirm_point'  => $item->sum_confirm_point,
                    'sum_exchange_point' => $item->sum_exchange_point,
                    'sum_lost_point'     => $item->sum_lost_point,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ]);
            }
        }
    }
}
