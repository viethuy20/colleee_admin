<?php
return [
    'fee_type' => [1 => '定額(P)', 2 => '定率(%)'],
    'test' => [0 => '一般用プログラム', 1 => 'テスト用プログラム'],
    'enable' => [0 => '不可', 1 => '可'],
    'target' => [0 => '非対象', 1 => '対象'],
    'multi_join' => [0 => '不可能', 1 => '可能', 2 => '当月不可能'],
    'device' => [1 => 'PC', 2 => 'iOS', 3 => 'Android'],
    'device2' => [7 => 'PC・SP', 1 => 'PCのみ', 6 => 'SPのみ'],
    'carrier' => [1 => 'DoCoMo', 2 => 'au', 3 => 'SoftBank'],
    'accept_days' => [0 => '即時', 1 => '1日程度',2 => '2～3日程度', 4 => '1週間程度',
        8 => '2週間程度', 13 => '1ヶ月程度', 31 => '1ヶ月以上'],
    'shop_category' => [1 => '定番人気ショップ', 2 => '美容・コスメ', 3 => '健康・サプリ',
        4 => '本・CD・DVD・音楽', 5 => 'ゲーム・玩具', 6 => 'キッチン・日用品・インテリア',
        7 => '家電・パソコン', 8 => '花・ギフト', 9 => 'グルメ・ドリンク', 10 => 'ペット用品',
        11 => '旅行・ホテル・レンタカー', 12 => 'キッズ・ベビー', 13 => 'アウトドア',
        14 => 'オフィス・事務用品', 15 => 'ファッション', 16 => 'その他'],

    'user_status' => [App\User::COLLEEE_STATUS => '正常', App\User::SELF_WITHDRAWAL_STATUS => '自主退会',
        App\User::OPERATION_WITHDRAWAL_STATUS => '運用退会', App\User::FORCE_WITHDRAWAL_STATUS => '不正退会',
        App\User::LOCK1_STATUS => '交換ロック', App\User::LOCK2_STATUS => '全ロック',
        App\User::SYSTEM_STATUS => 'システム',],
    'user_rank' => [0 => '一般', 1 => 'ブロンズ', 2 => 'シルバー', 3 => 'ゴールド', 4 => 'プラチナ'],
    'point_type' => [App\UserPoint::ROLLBACK_TYPE => '組戻し',
        App\UserPoint::PROGRAM_TYPE => '広告',
        App\UserPoint::MONITOR_TYPE => 'モニター',
        App\UserPoint::QUESTION_TYPE => 'アンケート',
        App\UserPoint::REVIEW_TYPE => '口コミ',
        App\UserPoint::ADMIN_TYPE => '管理者',
        App\UserPoint::BANK_TYPE => '金融機関振込',
        App\UserPoint::EMONEY_TYPE => '電子マネー交換',
        App\UserPoint::GIFT_CODE_TYPE => 'ギフトコード交換',
        App\UserPoint::OTHER_POINT_TYPE => '他社ポイント交換',
        App\UserPoint::OLD_PROGRAM_TYPE => '旧広告',
        App\UserPoint::SP_PROGRAM_TYPE => '特別広告',
        App\UserPoint::SP_PROGRAM_WITH_REWARD_TYPE => '成果あり特別広告',
        App\UserPoint::POINTBOX_TYPE => 'ポイントボックス移行',
        App\UserPoint::BIRTYDAY_BONUS_TYPE => '誕生日ボーナス',
        App\UserPoint::PROGRAM_BONUS_TYPE => 'ボーナス対象広告',
        App\UserPoint::ENTRY_BONUS_TYPE => 'お友達紹介ボーナス',
        App\UserPoint::GAME_BONUS_TYPE => 'アプリゲーム'],

    'sex' => [1 => '男性', 2 => '女性'],

    'auth_status' => [2 => '未承認', 0 => '承認', 1 => '却下'],

    'message_label' => [1 => '重要'],

    'credit_card_point_type' => [1 => 'Tポイント', 2 => 'Rポイント', 3 => 'JALマイル', 4 => 'ANAマイル'],
    'credit_card_brand' => [1 => 'VISA', 2 => 'MasterCard', 3 => 'JCB', 4 => 'アメリカン・エキスプレス', 5 => 'ダイナース'],
    'credit_card_emoney' => [1 => 'Suica', 2 => 'PASMO', 3 => '楽天Edy', 4 => 'nanaco', 5 => 'iD', 6 => 'WAON',
        7 => 'QUICPay'],
    'credit_card_insurance' => [1 => '国内旅行保険', 2 => '海外旅行保険', 3 => '盗難保険', 4 => 'ショッピング保険'],
    'credit_card_apple_pay' => [0 => '非対応', 1 => '対応'],

    'admin_role' => [\App\Admin::ADMIN_ROLE => '管理者', \App\Admin::SUPPORT_ROLE => 'サポート',
        \App\Admin::OPERATOR_ROLE => '運営者', \App\Admin::DRAFT_ROLE => '入稿'],

    'label_type' => [1 => 'カテゴリ(ショッピング)', 2 => 'カテゴリ(サービス)', 3 => '獲得方法', 4 => '人気条件', 5 => '参加上限'],

    'multi_join_tag' => [0 => '初回限定', 1 => 'リピートOK', 2 => '再登録OK'],

    'use_edit_type' => [
        \App\UserEditLog::INIT_TYPE => '初期値', \App\UserEditLog::EMAIL_TYPE => 'メールアドレス',
        \App\UserEditLog::TEL_TYPE => '電話番号', \App\UserEditLog::PASSWORD_TYPE => 'パスワード',
        \App\UserEditLog::EMAIL_REMIND_TYPE => 'メールアドレス(リマインダー)',
        \App\UserEditLog::PASSWORD_REMIND_TYPE => 'パスワード(リマインダー)',
    ],
];
