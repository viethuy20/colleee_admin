<?php
Route::resource('admins', 'AdminsController', ['only' => ['index', 'create', 'edit', 'store', 'destroy']]);
Route::post('/admins/reset', 'AdminsController@reset')
    ->name('admins.reset');

Route::get('/aff_rewards/test', function () {
    return view('aff_rewards.test');
});
