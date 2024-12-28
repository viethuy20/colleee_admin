<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'status', 'email_magazine', 'memo'
    ];

    protected $guarded = [
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'test' => 'boolean',
        'birthday' => 'datetime',
        'ticketed_at' => 'datetime',
        'actioned_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /** 正常. */
    const COLLEEE_STATUS = 0;
    /** 自主退会. */
    const SELF_WITHDRAWAL_STATUS = 1;
    /** 強制退会. */
    const FORCE_WITHDRAWAL_STATUS = 2;
    /** システム. */
    const SYSTEM_STATUS = 4;
    /** 運用退会. */
    const OPERATION_WITHDRAWAL_STATUS = 5;
    /** 交換ロック. */
    const LOCK1_STATUS = 6;
    /** ユーザー全機能ロック. */
    const LOCK2_STATUS = 7;

    /**
     * 日付を変形する属性
     * @var array
     */
    protected $dates = ['birthday', 'ticketed_at', 'actioned_at', 'deleted_at',];


    /**
     * Add extra attribute.
     */
    protected $appends = ['point' => null, 'exchange_point' => null, 'rank' => null];

    public function ranks()
    {
        return $this->hasMany(UserRank::class, 'user_id', 'id');
    }
    // @codingStandardsIgnoreStart
    public function bank_accounts()
    {
        // @codingStandardsIgnoreEnd
        return $this->hasMany(BankAccount::class, 'user_id', 'id')
            ->orderBy('id', 'desc');
    }
    public function logins()
    {
        return $this->hasMany(UserLogin::class, 'user_id', 'id')
            ->orderBy('id', 'desc');
    }
    public function points()
    {
        return $this->hasMany(UserPoint::class, 'user_id', 'id')
            ->orderBy('id', 'desc');
    }
    // @codingStandardsIgnoreStart
    public function exchange_requests()
    {
        // @codingStandardsIgnoreEnd
        return $this->hasMany(ExchangeRequest::class, 'user_id', 'id')
            ->orderBy('id', 'desc');
    }
    // @codingStandardsIgnoreStart
    public function friend_user()
    {
        // @codingStandardsIgnoreEnd
        return $this->belongsTo(User::class, 'friend_user_id');
    }
    // @codingStandardsIgnoreStart
    public function aff_accounts()
    {
        // @codingStandardsIgnoreEnd
        return $this->hasMany(AffAccount::class, 'user_id', 'id');
    }

    // @codingStandardsIgnoreStart
    public function edit_logs()
    {
        // @codingStandardsIgnoreEnd
        return $this->hasMany(UserEditLog::class, 'user_id', 'id');
    }

    public function line_account()
    {
        // @codingStandardsIgnoreEnd
        return $this->hasOne(LineAccount::class);
    }
    
    private static function getPrefix() : string
    {
        switch (config('app.env')) {
            case 'local':
                return 'L';
            case 'development':
                return 'D';
            default:
                return 'C';
        }
    }

    /**
     * ユーザーIDからユーザー名取得.
     * @param int $user_id ユーザーID
     * @return string ユーザー名
     */
    public static function getNameById(int $user_id) : string
    {
        return self::getPrefix().sprintf("%015d", $user_id);
    }

    /**
     * ユーザー名からユーザーID取得.
     * @param string $name ユーザー名
     * @return int ユーザーID
     */
    public static function getIdByName(string $name) : int
    {
        // 書式検証
        if (preg_match('/^'.self::getPrefix().'[0-9]{15}$/', $name)) {
            return intval(substr($name, 1), 10);
        }
        return 0;
    }

    /**
     * 名前取得.
     * @return string 名前
     */
    public function getNameAttribute() :string
    {
        return self::getNameById($this->id);
    }

    public function setStatusAttribute(int $status)
    {
        if ($this->attributes['status'] != $status && in_array($status, [self::OPERATION_WITHDRAWAL_STATUS,
            self::FORCE_WITHDRAWAL_STATUS], true)) {
            $this->deleted_at = Carbon::now();
        }
        $this->attributes['status'] = $status;
    }

    /**
     * ポイント取得.
     * @return int ポイント
     */
    public function getPointAttribute() : int
    {
        $last_user_point = $this->points()->first();
        return isset($last_user_point->id) ? $last_user_point->point : 0;
    }

    /**
     * 交換ポイント取得.
     * @return int 交換ポイント
     */
    public function getExchangedPointAttribute() : int
    {
        $last_user_point = $this->points()->first();
        if (isset($last_user_point->id)) {
            return $last_user_point->exchanged_point - $this->exchanging_point;
        }
        return 0;
    }

    /**
     * 交換中ポイント取得.
     * @return int ポイント
     */
    public function getExchangingPointAttribute() : int
    {
        // 交換中ポイントを取得
        return $this->exchange_requests()
            ->whereIn('status', [ExchangeRequest::WAITING_STATUS, ExchangeRequest::ERROR_STATUS])
            ->sum('point');
    }

    /**
     * ランク取得.
     * @return int ランク
     */
    public function getRankAttribute() : int
    {
        // 値を持っていなかった場合
        if (isset($this->appends['rank'])) {
            return $this->appends['rank'];
        }
        // ランク取得
        $now = Carbon::now();
        $user_rank = $this->ranks()
            ->ofTerm($now)
            ->first();
        $this->appends['rank'] = $user_rank->rank ?? 0;
        return $this->appends['rank'];
    }

    /**
     * 退会取得.
     * @return bool 退会
     */
    public function getIsWithdrawalAttribute() : bool
    {
        return in_array($this->status, [self::SELF_WITHDRAWAL_STATUS, self::OPERATION_WITHDRAWAL_STATUS,
            self::FORCE_WITHDRAWAL_STATUS], true);
    }

    /**
     * 交換可能取得.
     * @return bool 交換可能な場合はtrueを、不可能な場合はfalseを返す
     */
    public function getEnableExchangeAttribute() : bool
    {
        return $this->status == self::COLLEEE_STATUS;
    }

    /**
     * ポイント失効期限取得.
     * @return Carbon ポイント失効期限
     */
    public function getPointExpireAtAttribute() : Carbon
    {
        return $this->actioned_at->copy()
            ->startOfMonth()
            ->addMonths(6)
            ->endOfMonth();
    }

    /**
     * FancrewユーザーID.
     * @return string|NULL
     */
    public function getFancrewUserIdAttribute() : ?string
    {
        return $this->aff_accounts()
            ->ofType(AffAccount::FANCREW_TYPE)
            ->value('number');
    }

    public function scopeOfEmail($query, string $email)
    {
        // メールアドレスの書式を確認
        $validator = \Validator::make(
            ['email' => email_quote($email)],
            ['email' => ['required', 'email'],]
        );
        if ($validator->fails()) {
            return $query->whereRaw('1 <> 1');
        }

        $p_email = email_unquote($email);
        $parsed_email = explode('@', $p_email);
        $email_domain = array_pop($parsed_email);
        $email_user = implode('@', $parsed_email);

        // Gmailの場合は同一ユーザー別名メールアドレスも検索
        $gmail_domain = 'gmail.com';
        if ($email_domain == $gmail_domain) {
            $p_email_user = str_replace('.', '', $email_user);
            $p_email_user_list = explode('+', $p_email_user);
            $p_email_user = array_pop($p_email_user_list);
            if (!empty($p_email_user_list)) {
                $p_email_user = implode('+', $p_email_user_list);
            }
            return $query->where('email', 'like', '%@'.$gmail_domain)
                ->whereRaw(
                    "substring_index(replace(replace(email, ?, ''), '.', ''), '+', 1) = ?",
                    ['@'.$gmail_domain, $p_email_user]
                );
        }
        // Yahooメールの場合は同一ユーザー別名メールアドレスも検索
        $yahoo_domain = 'yahoo.co.jp';
        if ($email_domain == $yahoo_domain) {
            $p_email_user_list = explode('-', $email_user);
            $p_email_user = array_pop($p_email_user_list);
            if (!empty($p_email_user_list)) {
                $p_email_user = implode('-', $p_email_user_list);
            }
            return $query->where('email', 'like', '%@'.$yahoo_domain)
                ->whereRaw("substring_index(replace(email, ?, ''), '-', 1) = ?", ['@'.$yahoo_domain, $p_email_user]);
        }
        // MSNメールの場合は同一ユーザー別名メールアドレスも検索
        $msn_domain_list = ['hotmail.co.jp', 'live.jp', 'outlook.jp', 'outlook.com'];
        if (in_array($email_domain, $msn_domain_list, true)) {
            $p_email_user_list = explode('+', $email_user);
            $p_email_user = array_pop($p_email_user_list);
            if (!empty($p_email_user_list)) {
                $p_email_user = implode('+', $p_email_user_list);
            }
            return $query->where('email', 'like', '%@'.$email_domain)
                ->whereRaw("substring_index(replace(email, ?, ''), '+', 1) = ?", ['@'.$email_domain, $p_email_user]);
        }

        return $query->where('email', '=', $p_email);
    }

    public static function getProhibitedTotal($start_at, $end_at)
    {
        return  User::whereBetween('deleted_at', [$start_at, $end_at])
            ->where('status', '=', User::FORCE_WITHDRAWAL_STATUS)
            ->count();
    }

    public static function getDeletedTotal($start_at, $end_at)
    {
        return User::whereBetween('deleted_at', [$start_at, $end_at])
            ->where('status', '<>', User::FORCE_WITHDRAWAL_STATUS)//強制退会以外
            ->count();
    }

    public static function getCreatedTotal($start_at, $end_at)
    {
        return User::whereBetween('created_at', [$start_at, $end_at])->count();
    }

    public static function getDayCreatedTotal($start_at, $end_at)
    {
        return User::select(DB::raw('Date(created_at) as date'), DB::raw('COUNT(*) as number_of_users'))
            ->whereBetween('created_at', [$start_at, $end_at])
            ->groupBy(DB::raw('Date(created_at)'))->orderBy('date', 'asc')->get();
    }

    public static function getWeekCreatedTotal($start_at, $end_at)
    {
        return User::select(DB::raw('Week(created_at) as week'), DB::raw('COUNT(*) as number_of_users'))
            ->whereBetween('created_at', [$start_at, $end_at])
            ->groupBy(DB::raw('Week(created_at)'))->orderBy('week', 'asc')->get();
    }

    public static function getMonthCreatedTotal($start_at, $end_at)
    {
        return User::select(DB::raw('Month(created_at) as month'), DB::raw('COUNT(*) as number_of_users'))
            ->whereBetween('created_at', [$start_at, $end_at])
            ->groupBy(DB::raw('Month(created_at)'))->orderBy('month', 'asc')->get();
    }

    public static function getDayProhibitedTotal($start_at, $end_at)
    {
          return User::select(DB::raw('Date(deleted_at) as date'), DB::raw('COUNT(*) as number_of_users'))
            ->whereBetween('deleted_at', [$start_at, $end_at])
            ->where('status', '=', User::FORCE_WITHDRAWAL_STATUS)
            ->groupBy(DB::raw('Date(deleted_at)'))->orderBy('date', 'asc')->get();
    }

    public static function getWeekProhibitedTotal($start_at, $end_at)
    {
        return  User::select(DB::raw('Week(deleted_at) as week'), DB::raw('COUNT(*) as number_of_users'))
            ->whereBetween('deleted_at', [$start_at, $end_at])
            ->where('status', '=', User::FORCE_WITHDRAWAL_STATUS)
            ->groupBy(DB::raw('Week(deleted_at)'))->orderBy('week', 'asc')->get();
    }

    public static function getMonthProhibitedTotal($start_at, $end_at)
    {
        return  User::select(DB::raw('Month(deleted_at) as month'), DB::raw('COUNT(*) as number_of_users'))
            ->whereBetween('deleted_at', [$start_at, $end_at])
            ->where('status', '=', User::FORCE_WITHDRAWAL_STATUS)
            ->groupBy(DB::raw('Month(deleted_at)'))->orderBy('month', 'asc')->get();
    }

    public static function getDayDeletedTotal($start_at, $end_at)
    {
        return User::select(DB::raw('Date(deleted_at) as date'), DB::raw('COUNT(*) as number_of_users'))
            ->whereBetween('deleted_at', [$start_at, $end_at])
            ->where('status', '<>', User::FORCE_WITHDRAWAL_STATUS)//強制退会以外
            ->groupBy(DB::raw('Date(deleted_at)'))->orderBy('date', 'asc')->get();
    }

    public static function getWeekDeletedTotal($start_at, $end_at)
    {
        return User::select(DB::raw('Week(deleted_at) as week'), DB::raw('COUNT(*) as number_of_users'))
            ->whereBetween('deleted_at', [$start_at, $end_at])
            ->where('status', '<>', User::FORCE_WITHDRAWAL_STATUS)//強制退会以外
            ->groupBy(DB::raw('Week(deleted_at)'))->orderBy('week', 'asc')->get();
    }

    public static function getMonthDeletedTotal($start_at, $end_at)
    {
        return User::select(DB::raw('Month(deleted_at) as month'), DB::raw('COUNT(*) as number_of_users'))
            ->whereBetween('deleted_at', [$start_at, $end_at])
            ->where('status', '<>', User::FORCE_WITHDRAWAL_STATUS)//強制退会以外
            ->groupBy(DB::raw('Month(deleted_at)'))->orderBy('month', 'asc')->get();
    }


}
