<?php

use Illuminate\Support\Facades\Auth;

Route::get('/logout/', 'AuthController@logout')->name('logout');

// レポート
Route::get('/', 'ReportsController@getList')->name('website.index');
Route::get('/reports', 'ReportsController@getList')->name('reports.index');
Route::get('/reports/list/{ym}', 'ReportsController@getList')
    ->where('ym', '[0-9]{6}')
    ->name('reports.list');
Route::get('/reports/monthly', 'ReportsController@getMonthly')->name('reports.monthly');
Route::get('/reports/csv_monthly', 'ReportsController@getCsvMonthly')->name('reports.csv_monthly');
Route::get('/reports/user_link_fanspot', 'ReportsController@getUserLinkFanspot')->name('reports.user_link_fanspot');
Route::get('/reports/user_link_cp', 'ReportsController@getUserLinkCP')->name('reports.user_link_cp');
Route::post('/reports/csv_user', 'ReportsController@getCsvUser')->name('reports.csvUser');

// プログラム
Route::resource('programs', 'ProgramsController', ['only' => ['index', 'create', 'edit', 'store', 'destroy']]);
Route::post('/programs/copy', 'ProgramsController@copy')
    ->name('programs.copy');
Route::post('/programs/{program}/enable', 'ProgramsController@enable')
    ->where('program', '[0-9]+')
    ->name('programs.enable');
Route::get('/programs/enable_program/', 'ProgramsController@ajaxEnableProgram')
    ->name('programs.enable_program');
Route::get('/programs/label_add/', 'ProgramsController@ajaxAddLabelTag')
    ->name('programs.add_label_tag');
Route::get('/programs/get_url/', 'ProgramsController@ajaxGetUrl')
    ->name('programs.get_url');
Route::get('/programs/add_note_stockcv/', 'ProgramsController@ajaxAddNoteStockCV')
    ->name('programs.add_note_stockcv');
Route::get('/programs/add_question/', 'ProgramsController@ajaxAddQuestion')
    ->name('programs.add_question');
Route::get('/programs/label_remove/', 'ProgramsController@ajaxRemoveLabelTag')
    ->name('programs.remove_label_tag');
Route::get('/programs/show_course/', 'ProgramsController@ajaxShowCourse')
    ->name('programs.show_course');
Route::get('/programs/add_course/', 'ProgramsController@ajaxAddCourse')
    ->name('programs.add_course');
Route::get('/programs/add_point/', 'ProgramsController@ajaxAddPoint')
    ->name('programs.add_point');
Route::resource('program_campaigns', 'ProgramCampaignsController', ['only' => [ 'edit', 'store']]);
Route::get('/program_campaigns/{program}', 'ProgramCampaignsController@index')
    ->where('program', '[0-9]+')
    ->name('program_campaigns.index');
Route::get('/program_campaigns/create/{program}', 'ProgramCampaignsController@create')
    ->where('program', '[0-9]+')
    ->name('program_campaigns.create');
Route::get('/app_driver_programs', 'AppDriverProgramsController@index')
    ->name('app_driver_programs.index');
Route::post('/app_driver_programs/{app_driver_program}', 'AppDriverProgramsController@show')
    ->where('app_driver_program', '[0-9]+')
    ->name('app_driver_programs.show');
Route::post('/app_driver_programs/create', 'AppDriverProgramsController@create')
    ->name('app_driver_programs.create');
//question
Route::resource('program_questions', 'ProgramQuestionsController', ['only' => ['edit', 'store', 'destroy']]);
Route::get('/program_questions/create/{maxDispOrder}/{program}/', 'ProgramQuestionsController@create')
    ->where('maxDispOrder', '[0-9]+')
    ->where('program', '[0-9]+')
    ->name('program_questions.create');
// コース
Route::resource('courses', 'CoursesController', ['only' => ['edit', 'store', 'destroy']]);
Route::get('/courses/create/{program}/', 'CoursesController@create')
    ->where('program', '[0-9]+')
->name('courses.create');
Route::post('/courses/{course}/enable', 'CoursesController@enable')
    ->where('course', '[0-9]+')
    ->name('courses.enable');

// ポイント
Route::resource('points', 'PointsController', ['only' => ['edit', 'store']]);
Route::get('/points/create/{program}/{course?}', 'PointsController@create')
    ->where('program', '[0-9]+')
    ->where('course', '[0-9]+')
    ->name('points.create');

// アフィリエイト
Route::resource('affiriates', 'AffiriatesController', ['only' => ['edit', 'store']]);
Route::get('/affiriates/create/{program}/', 'AffiriatesController@create')
    ->where('program', '[0-9]+')
    ->name('affiriates.create');

// 特別プログラム
Route::resource('sp_programs', 'SpProgramsController', ['only' => ['edit', 'store', 'destroy']]);
Route::get('/sp_programs/create/{sp_program_type}', 'SpProgramsController@create')
    ->where('sp_program_type', '[0-9]+')
    ->name('sp_programs.create');
Route::get('/sp_programs/{sp_program_type}/list', 'SpProgramsController@getList')
    ->where('sp_program_type', '[0-9]+')
    ->name('sp_programs.list');
Route::post('/sp_programs/{sp_program}/enable', 'SpProgramsController@enable')
    ->where('sp_program', '[0-9]+')
    ->name('sp_programs.enable');

// ASP
Route::resource('asps', 'AspsController', ['only' => ['index', 'edit', 'store']]);

// タグ
Route::resource('tags', 'TagsController', ['only' => ['index', 'create', 'edit', 'store']]);

// 口コミ
Route::get('/reviews', 'ReviewsController@getList')
    ->name('reviews.index');
Route::get('/reviews/{status}/list', 'ReviewsController@getList')
    ->where('status', '-?[0-9]+')
    ->name('reviews.list');
Route::post('/reviews/change_status', 'ReviewsController@changeStatus')
    ->name('reviews.change_status');

// 口コミ配布ポイント管理
Route::resource('review_point_management', 'ReviewPointManagementController');

// コンテンツ
Route::resource('contents', 'ContentsController', ['only' => ['edit', 'store', 'destroy']]);
Route::get('/contents/{spot}/list/', 'ContentsController@getList')
    ->where('spot', '[0-9]+')
    ->name('contents.list');
Route::get('/contents/{spot}/create/', 'ContentsController@create')
    ->where('spot', '[0-9]+')
    ->name('contents.create');
Route::post('/contents/store_img', 'ContentsController@storeImg')
    ->name('contents.store_img');

//Popupads
Route::resource('popup_ads', 'PopupAdsController', ['only' => ['index', 'create', 'edit', 'store', 'destroy']]);
Route::get('/popup_ads/get_program/', 'PopupAdsController@ajaxGetProgram')
    ->name('popup_ads.get_program');
// 添付
Route::get('/attachments/images/', 'AttachmentsController@images')
    ->name('attachments.images');
Route::post('/attachments/upload/', 'AttachmentsController@upload')
    ->name('attachments.upload');

// ユーザー
Route::resource('users', 'UsersController', ['only' => ['index', 'edit', 'store']]);
Route::get('/users/point_history/{user}', 'UsersController@pointHistory')
    ->where('user', '[0-9]+')
    ->name('users.point_history');
Route::get('/users/login_history/{user}', 'UsersController@loginHistory')
    ->where('user', '[0-9]+')
    ->name('users.login_history');
Route::get('/users/edit_history/{user}', 'UsersController@editHistory')
    ->where('user', '[0-9]+')
    ->name('users.edit_history');
Route::post('/users/reset_tel', 'UsersController@resetTel')
    ->name('users.reset_tel');
Route::post('/users/email_reminder', 'UsersController@emailReminder')
    ->name('users.email_reminder');
Route::get('/users/kpi/', 'UsersController@kpi')
    ->name('users.kpi');
Route::post('/users/getDataGrap', 'UsersController@getDataGrap')
    ->name('users.grap');
Route::get('/users/csv', function () {
    if (auth()->user()->role == \App\Admin::DRAFT_ROLE) {
        return abort(403, 'This action is unauthorized.');
    }
    return view('users.csv');
})->name('users.csv');
Route::get('/users/export.csv', 'UsersController@export')
    ->name('users.export_csv');

// アンケート
Route::resource('questions', 'QuestionsController', ['only' => ['index', 'create', 'edit', 'store', 'destroy']]);
Route::post('/questions/enable/', 'QuestionsController@enable')
    ->name('questions.enable');
Route::get('/user_answers', 'UserAnswersController@index')
    ->name('user_answers.index');
Route::post('/user_answers/change_status', 'UserAnswersController@changeStatus')
    ->name('user_answers.change_status');
Route::get('/pre_aff_rewards/{user}/list', 'PreAffRewardsController@getList')
    ->where('user', '[0-9]+')
    ->name('pre_aff_rewards.list');
Route::post('/pre_aff_rewards/unblock', 'PreAffRewardsController@unblock')
    ->name('pre_aff_rewards.unblock');

// 交換
Route::resource('exchange_infos', 'ExchangeInfosController', ['only' => ['index', 'edit', 'store']]);
Route::get('/exchange_infos/{type}', 'ExchangeInfosController@show')
    ->where('type', '[0-9]+')
    ->name('exchange_infos.show');
Route::get('/exchange_infos/{type}/create', 'ExchangeInfosController@create')
    ->where('type', '[0-9]+')
    ->name('exchange_infos.create');
Route::get('/exchange_requests/import', function () {
    if (Auth::guard('admin')->user()->role == \App\Admin::DRAFT_ROLE) {
        return abort(403, 'This action is unauthorized.');
    }
    return view('exchange_request_import');
})->name('exchange_requests.import');
Route::post('/exchange_requests/import', 'ExchangeRequestsController@import');


// 金融機関
Route::get('/banks', 'BanksController@index')
    ->name('banks.index');
Route::get('/banks/import/', function () {
    return view('banks.import');
});
Route::post('/banks/import/', 'BanksController@import')
    ->name('banks.import');

// ギフトコード
Route::get('/gift_codes', 'GiftCodesController@index')
    ->name('gift_codes.index');
Route::post('/gift_codes/{exchange_request}/resend_request', 'GiftCodesController@resend')
    ->where('gift_codes', '[0-9]+')
    ->name('gift_codes.resend');

// ドットマネー
Route::get('/dot_money', 'DotMoneyController@index')
    ->name('dot_money.index');

// Dポイント
Route::get('/d_point', 'DPointController@index')
    ->name('d_point.index');

// LINE Pay
Route::get('/line_pay', 'LinePayController@index')
->name('line_pay.index');

// PayPay
Route::get('/paypay', 'PaypayController@index')
->name('paypay.index');

// KDOL
Route::get('/kdol', 'KdolController@index')
->name('kdol.index');
// デジタルギフトPayPal
Route::get('/digital_gift_paypal', 'DigitalGiftPaypalController@index')
->name('digital_gift_paypal.index');
Route::post('/digital_gift_paypal/{exchange_request}/resend_request', 'DigitalGiftPaypalController@resend')
    ->name('digital_gift_paypal.resend');


// デジタルギフトJALMile
Route::get('/jalmile', 'DigitalGiftJalMileController@index')
->name('jalmile.index');
Route::post('/jalmile/{exchange_request}/resend_request', 'DigitalGiftJalMileController@resend')
    ->name('jalmile.resend');

// クリック
Route::get('/external_links', 'ExternalLinksController@index')
    ->name('external_links.index');

// 成果
Route::get('/aff_rewards', 'AffRewardsController@index')
    ->name('aff_rewards.index');
Route::get('/aff_rewards/export.csv', 'AffRewardsController@export')
    ->name('aff_rewards.export_csv');

// 特集
Route::resource('feature_programs', 'FeatureProgramsController', ['only' => ['index', 'create', 'edit', 'store',
    'destroy']]);
Route::post('/feature_programs/{feature_program}/enable', 'FeatureProgramsController@enable')
    ->where('feature_program', '[0-9]+')
    ->name('feature_programs.enable');
Route::get('/feature_programs/sub_category/', 'FeatureProgramsController@ajaxSubCategory')
    ->name('feature_programs.sub_category');

Route::resource('feature_sub_categories', 'FeatureSubCategoriesController', ['only' => ['index', 'create', 'edit',
    'store']]);

// クレジットカード
Route::resource('credit_cards', 'CreditCardsController', ['only' => ['index', 'edit', 'store', 'destroy']])
    ->parameters(['credit_cards' => 'program']);
Route::post('/credit_cards/{program}/enable', 'CreditCardsController@enable')
    ->where('program', '[0-9]+')
    ->name('credit_cards.enable');

//管理者PW変更
Route::get('/admins/update_password/', function () {
    return view('admins.update_password');
})->name('admins.update_password');

Route::post('/admins/update_password/', 'AdminsController@updatePassword');

// ラベル
Route::resource('labels', 'LabelsController', ['only' => ['edit', 'store', 'destroy']]);
Route::get('/labels/{type}/list', 'LabelsController@getList')
    ->where('type', '[0-9]+')
    ->name('labels.list');
Route::post('/labels/{label}/enable', 'LabelsController@enable')
    ->where('label', '[0-9]+')
    ->name('labels.enable');

// メールアドレスブロックドメイン管理
Route::resource(
    'email_block_domains',
    'EmailBlockDomainsController',
    ['only' => ['index', 'create', 'edit', 'store', 'destroy']]
);
Route::post('/email_block_domains/{email_block_domain}/enable', 'EmailBlockDomainsController@enable')
    ->where('email_block_domain', '[0-9]+')
    ->name('email_block_domains.enable');

Route::post('/aff_accounts/export_removed_fancrew_list', 'AffAccountsController@exportRemovedFancrewList')
    ->name('aff_accounts.export_removed_fancrew_list');

// メンテナンス管理
Route::resource(
    'maintes',
    'MaintesController',
    ['only' => ['index', 'edit', 'store', 'destroy']]
);
Route::get('/maintes/{type}/create', 'MaintesController@create')
    ->where('type', '[0-9]+')
    ->name('maintes.create');


// 友達紹介ボーナス
Route::get('/friends', 'FriendsController@index')
->name('friends.index');
Route::get('/friends/newdata', 'FriendsController@newdata')
->name('friends.newdata');
Route::get('/friends/show/{id}', 'FriendsController@show')
->where('friends', '[0-9]+')
->name('friends.show');
Route::post('/friends/show/{id}', 'FriendsController@show')
->where('friends', '[0-9]+')
->name('friends.show');
Route::post('/friends/update', 'FriendsController@update')
->name('friends.update');
Route::post('/friends/create', 'FriendsController@create')
->name('friends.create');

//entries page
Route::resource('entries', 'EntriesController', ['only' => ['index', 'edit', 'store', 'create']]);

//おすすめ広告プログラム
Route::resource('recommend_program', 'RecommendProgramController', ['only' => ['index', 'edit', 'store', 'create']]);

Route::get('/recommend_program/destroy/{id}', 'RecommendProgramController@destroy')
->name('recommend_program.destroy');
