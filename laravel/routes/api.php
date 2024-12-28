<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

//PayPay管理画面組み戻し用API
Route::post('/paypay/rollback/{exchange_request_id}', 'PaypayController@rollback')
    ->name('paypay.rollback');

    Route::get('/recommend_program/program/', 'RecommendProgramController@get_program')
->name('recommend_program.program');
