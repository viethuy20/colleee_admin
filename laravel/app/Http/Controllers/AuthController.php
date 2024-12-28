<?php
namespace App\Http\Controllers;

use Auth;
use Hash;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

/**
 * 認証コントローラー.
 */
class AuthController extends Controller
{
    /**
     * ログイン.
     * @param Request $request {@link Request}
     */
    public function login(Request $request)
    {
        //
        $this->validate(
            $request,
            [
                'email' => ['required',],
                'password' => ['required',],
            ],
            [],
            [
                'email' => 'メールアドレス',
                'password' => 'パスワード',
            ]
        );
        
        // 認証実行
        if (Auth::guard('admin')->attempt(['email' => $request->input('email'),
            'password' => $request->input('password'), 'status' => 0], true)) {
            if (Auth::guard('admin')->user()->role == \App\Admin::DRAFT_ROLE) {
                return redirect('/programs');
            }
            return redirect('/');
        }

        return redirect()
            ->back()
            ->withInput()
            ->with('message', 'ログインに失敗しました');
    }
    
    /**
     * ログアウト.
     */
    public function logout()
    {
        // ログアウト
        Auth::logout();
        // トップページへ
        return redirect('/login');
    }
}
