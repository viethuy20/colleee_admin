<?php
// 成果インポート
Route::get('/aff_rewards/import', 'AffRewardsController@import')
    ->name('aff_rewards.import');
Route::post('/aff_rewards/import_program_csv', 'AffRewardsController@importProgramCSV')
    ->name('aff_rewards.import_program_csv');
Route::post('/aff_rewards/import_programless_csv', 'AffRewardsController@importProgramlessCSV')
    ->name('aff_rewards.import_programless_csv');
Route::get('/aff_rewards/achievement', 'AffRewardsController@achievement')
    ->name('aff_rewards.achievement');
Route::get('/aff_rewards/export-achievement', 'AffRewardsController@exportAchievement')
    ->name('aff_rewards.export-achievement');

// 補填
Route::get('/user_points/', 'UserPointsController@index')
    ->name('user_points.index');
Route::post('/user_points/import_program/', 'UserPointsController@importProgram')
    ->name('user_points.import_program');
Route::post('/user_points/import_sp_program/', 'UserPointsController@importSpProgram')
    ->name('user_points.import_sp_program');

// 銀行口座一覧
Route::get('/banks/{user}/account_list', 'BanksController@showAccountList')
    ->where('user', '[0-9]+')
    ->name('banks.account_list');
// 銀行口座リセット
Route::delete('/banks/{user}/delete_account', 'BanksController@deleteAccount')
    ->where('user', '[0-9]+')
    ->name('banks.delete_account');

Route::post('/aff_accounts/removed_fancrew', 'AffAccountsController@removedFancrew')
    ->name('aff_accounts.removed_fancrew');

Route::post('/users/delete_nonaction_users', 'UsersController@deleteNonactionUsers')
    ->name('users.delete_nonaction_users');
