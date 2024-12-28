<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Test\Success::class,
        Test\Error::class,
        //
        Bank\Import::class,

        // 交換処理
        PaymentGateway\Transfer::class,
        Voyage\Issu::class,
        NttCard\Issu::class,
        NttCard\Expire::class,
        DotMoney\Deposit::class,
        DPoint\Grant::class,
        LinePay\Deposit::class,
        PayPay\GiveCashback::class,
        PayPay\CheckCashback::class,
        PayPay\CheckRetryCashback::class,
        PayPay\CheckCashbackReversalDetails::class,
        Kdol\CheckCashback::class,
        Kdol\GiveCashback::class,

        // 照会
        PaymentGateway\Confirm::class,

        Cuenote\Import::class,
        Cuenote\BackupDelete::class,

        // 誕生日
        Bonus\Birthday::class,
        // 紹介ボーナス
        Bonus\Entry::class,
        // 獲得ボーナス
        Bonus\Program::class,
        // ユーザー友達紹介報酬情報へ初期入力
        Bonus\InsertFriendPoint::class,
        Bonus\InsertFriendReferralBonus::class,
        Bonus\InsertFriendReturnBonus::class,

        UserPoint\ImportProgram::class,
        UserPoint\ImportSpProgram::class,

        // パーティション
        Partition\AffReward::class,
        Partition\PreAffReward::class,
        Partition\ExchangeRequest::class,
        Partition\UserLogin::class,
        Partition\EmailToken::class,
        Partition\ExternalLink::class,
        Partition\Report::class,
        Partition\History::class,
        Partition\OstToken::class,

        // ランク更新
        UserRank\Update::class,
        // 交換申し込みインポート
        ExchangeRequest\Import::class,
        // 交換申し込みアラート
        ExchangeRequest\Alert::class,

        // 通知
        Notice\Friend::class,

        // ユーザー失効
        User\Expire::class,
        User\Refresh::class,

        // ポイント失効
        UserPoint\Expire::class,

        // データバックアップ
        Backup\UserProvision::class,

        // 削除
        UserPoint\Delete::class,
        UserPoint\BackupDelete::class,
        User\Delete::class,
        User\BackupDelete::class,
        UserRank\Delete::class,
        UserRank\BackupDelete::class,
        BankAccount\Delete::class,
        BankAccount\BackupDelete::class,
        UserEditLog\Delete::class,
        UserEditLog\BackupDelete::class,

        // AppDriver更新
        Program\AppDriver::class,

        // Up down stock cv
        Program\UpDownStock::class,

        // 月次レポート作成
        Report\Monthly::class,

        // GreeAdsRewardOffer更新
        Program\GreeAdsRewardOffer::class,

        // SkyFlagOffer更新
        Program\SkyFlagOffer::class,

        // LogrecoAIアイテムマスタ更新
        LogrecoAi\Itemmaster::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
