<?php
namespace App\Console\Bonus;

use Carbon\Carbon;

use App\Console\BaseCommand;

use App\User;
use App\UserRank;
use WrapPhp;

/**
 * Description of Birthday
 *
 * @author t_moriizumi
 */
class Birthday extends BaseCommand {
    const POINT_TITLE = '誕生日';

    protected $tag = 'bonus:birthday';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bonus:birthday {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Birthday bonus';

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
        // タグ作成
        $this->info('start');

        $date_arg = $this->argument('date');
        $now = isset($date_arg) ? Carbon::parse($date_arg) : Carbon::now();
        $birth_list = [$now->format("md")];
        // うるう年
        if ($now->month == 2 && $now->day == 29) {
            $this->info('success');
            return 0;
        }
        if ($now->month == 2 && $now->day == 28) {
            $birth_list[] = $now->copy()
                    ->addDays(1)
                    ->format("md");
        }

        $p_list = array_fill(0, WrapPhp::count($birth_list), '?');
        $where_raw = sprintf("DATE_FORMAT(birthday, '%%m%%d') IN (%s)", implode(',', $p_list));

        $user_id = 1;

        while(true) {
            $user_list = User::whereIn('status', [User::COLLEEE_STATUS, User::LOCK1_STATUS, User::LOCK2_STATUS])
                    ->where('email_status', '=', 0)
                    ->whereRaw($where_raw, $birth_list)
                    ->whereExists(function($query) use ($now){
                        $query->select(\DB::raw(1))
                                ->from('user_ranks')
                                ->whereIn('rank', [UserRank::RANK_SILVER, UserRank::RANK_GOLD])
                                ->whereColumn('user_ranks.user_id', '=', 'users.id')
                                ->where('stop_at', '>=', $now)
                                ->where('start_at', '<=', $now);
                    })
                    ->where('id', '>', $user_id)
                    ->orderBy('id', 'ASC')
                    ->take(1000)
                    ->get();
            // 終了
            if ($user_list->isEmpty()) {
                break;
            }

            foreach ($user_list as $user) {
                $user_id = $user->id;

                //\Log::info('birthday[user_id:'.$user_id.']');
                // メール送信を実行
                try{
                    $mailable = new \App\Mail\Colleee($user->email, 'bonus_birthday', ['user_name' => $user->nickname ?? $user->name]);
                    \Mail::send($mailable);
                } catch(\Exception $e){
                }
            }
        }

        //
        $this->info('success');

        return 0;
    }
}
