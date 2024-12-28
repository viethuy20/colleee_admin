<?php

namespace App\Console\Commands;

use App\AffReward;
use App\Kpi;
use App\User;
use App\UserLogin;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SaveKpi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'save:kpi';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Save kpi';

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
        try {
            $date     = Carbon::yesterday();
            $date     = $date->toDateString();
            $start_at = $date . ' 00:00:00';
            $end_at   = $date . ' 23:59:59';

            $kpi                = [];
            $kpi['login_total'] = UserLogin::whereBetween('created_at', [$start_at, $end_at])
                ->count('user_id');
            $kpi['unique_login_total'] = UserLogin::whereBetween('created_at', [$start_at, $end_at])
                ->distinct('user_id')
                ->count('user_id');

            // アクション人数
            $kpi['unique_action_total'] = AffReward::orWhere(function ($query) use ($start_at, $end_at) {
                $query->where(function ($query) use ($start_at, $end_at) {
                    $query->whereNull('actioned_at');
                    $query->whereBetween('created_at', [$start_at, $end_at]);
                });
                $query->orWhere(function ($query) use ($start_at, $end_at) {
                    $query->where(function ($query) use ($start_at, $end_at) {
                        $query->whereNotNull('actioned_at');
                        $query->whereBetween('actioned_at', [$start_at, $end_at]);
                    });
                });
            })
                ->distinct('user_id')
                ->count('user_id');

            $kpi['created_total'] = User::whereBetween('created_at', [$start_at, $end_at])->count();
            // 新規入会アクション人数
            $kpi['created_action_total'] = AffReward::whereIn('user_id', function ($query) use ($start_at, $end_at) {
                $query->select('id')
                    ->from('users')
                    ->whereBetween('created_at', [$start_at, $end_at]);
            })
                ->where(function ($query) use ($start_at, $end_at) {
                    $query->where(function ($query) use ($start_at, $end_at) {
                        $query->whereNull('actioned_at');
                        $query->whereBetween('created_at', [$start_at, $end_at]);
                    });
                    $query->orWhere(function ($query) use ($start_at, $end_at) {
                        $query->where(function ($query) use ($start_at, $end_at) {
                            $query->whereNotNull('actioned_at');
                            $query->whereBetween('actioned_at', [$start_at, $end_at]);
                        });
                    });
                })
                ->distinct('user_id')
                ->count('user_id');
            $kpi['prohibited_total'] = User::whereBetween('deleted_at', [$start_at, $end_at])
                ->where('status', '=', User::FORCE_WITHDRAWAL_STATUS)
                ->count();
            $kpi['deleted_total'] = User::whereBetween('deleted_at', [$start_at, $end_at])
                ->whereIn('status', [User::SELF_WITHDRAWAL_STATUS, User::OPERATION_WITHDRAWAL_STATUS])
                ->count();

            $objKpi                                     = new Kpi();
            $objKpi->number_of_logins                   = $kpi['login_total'];
            $objKpi->number_of_logins_uu                = $kpi['unique_login_total'];
            $objKpi->number_of_actions_uu               = $kpi['unique_action_total'];
            $objKpi->action_rate                        = $kpi['unique_login_total'] > 0 ? number_format($kpi['unique_action_total'] * 100.0 / $kpi['unique_login_total'], 3) : 0;
            $objKpi->number_of_new_members              = $kpi['created_total'];
            $objKpi->number_of_new_enrollment_actions   = $kpi['created_action_total'];
            $objKpi->number_of_new_action_rate          = $kpi['created_total'] > 0 ? number_format($kpi['created_action_total'] * 100.0 / $kpi['created_total'], 3) : 0;
            $objKpi->number_of_unauthorized_withdrawals = $kpi['prohibited_total'];
            $objKpi->number_of_withdrawals              = $kpi['deleted_total'];
            $objKpi->start_at                           = $date;
            $objKpi->end_at                             = $date;
            $objKpi->save();
        } catch (Exception $ex) {
            \Log::error($ex->getMessage());
        }
    }
}
