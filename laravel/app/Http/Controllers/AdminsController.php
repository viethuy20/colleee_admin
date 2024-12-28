<?php
namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Mail;

use App\Admin;
use App\Http\Controllers\Controller;

/**
 * 管理者管理コントローラー.
 */
class AdminsController extends Controller
{
    /**
     * 代理店一覧.
     */
    public function index()
    {
        // 管理者一覧を取得
        if (Auth::user()->role == \App\Admin::DRAFT_ROLE) {
            return abort(403, 'This action is unauthorized.');
        }
        $admin_list = Admin::orderBy('id', 'asc')->get();
        return view('admins.list', ['admin_list' => $admin_list]);
    }
    
    /**
     * 管理者作成.
     */
    public function create()
    {
        if (Auth::user()->role == \App\Admin::DRAFT_ROLE) {
            return abort(403, 'This action is unauthorized.');
        }
        return $this->edit(Admin::getDefault());
    }
    
    /**
     * 管理者更新.
     * @param Admin $admin 管理者
     */
    public function edit(Admin $admin)
    {
        return view('admins.edit', ['admin' => $admin]);
    }
    
    /**
     * 管理者保存.
     * @param Request $request {@link Request}
     */
    public function store(Request $request)
    {
        $ignore_user_id = $request->input('id');

        $role_keys = array_keys(config('map.admin_role'));
    
        $this->validate(
            $request,
            [
                'id' => ['nullable', 'integer'],
                'name' => ['required', 'max:256'],
                'email' => ['required', 'custom_email:1',
                    Rule::unique('admins', 'email')->where(function ($query) use ($ignore_user_id) {
                        if (isset($ignore_user_id)) {
                            $query->where('id', '<>', $ignore_user_id);
                        }
                        $query->where('status', '<>', 1);
                    })
                ],
                'role' => ['required', 'integer', 'in:'.implode(',', $role_keys)],
            ],
            [],
            [
                'id' => 'ID',
                'name' => '名称',
                'email' => 'メールアドレス',
                'role' => '権限',
            ]
        );

        $password = null;
        // 初期データ取得
        $admin = null;
        if ($request->filled('id')) {
            //更新の時はPWを更新しない
            $admin = Admin::find($request->input('id'));
        } else {
            //パスワード自動生成
            $admin = Admin::getDefault();
            $password = substr(str_shuffle('1234567890abcdefghijklmnopqrstuvwxyz'), 0, 8);
            // パスワード
            $admin->password = app()->make('hash')->make($password);
        }

        $admin->fill($request->only(['name', 'email', 'role',]));
        // トランザクション処理
        $res = DB::transaction(function () use ($admin) {
            // 登録実行
            $admin->save();
            return true;
        });
        
        // 失敗した場合
        if (empty($res)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('message', '管理者の編集に失敗しました');
        }
        
        if (isset($password)) {
            $email = $admin->email;
            // メール送信を実行
            Mail::send(
                ['text' => 'emails.admin_password'],
                ['email' => $email, 'password' => $password, 'title'=> '新規アカウント発行'],
                function ($m) use ($email) {
                    $m->to($email)->subject('colleee管理画面');
                }
            );
        }
        
        return redirect(route('admins.edit', ['admin' => $admin]))->with('message', '管理者の編集に成功しました');
    }
    
    /**
     * 管理者削除.
     * @param Admin $admin 管理者
     */
    public function destroy(Admin $admin)
    {
        //論理削除
        $admin->status = 1;
        $admin->deleted_at = Carbon::now();
        // トランザクション処理
        $res = DB::transaction(function () use ($admin) {
            // 削除実行
            $admin->save();
            return true;
        });
        
        return redirect()
            ->back()
            ->with('message', empty($res) ? '管理者の削除に失敗しました' : '管理者の削除に成功しました');
    }
    
    
    /**
     * パスワード再設定.
     * @param Request $request {@link Request}
     */
    public function reset(Request $request)
    {
        $this->validate(
            $request,
            [
                'admin_id' => ['required', Rule::exists('admins', 'id')],
            ],
            [],
            [
                'admin_id' => 'ID',
            ]
        );
    
        $password = null;
        $admin = null;
       
        $admin = Admin::find($request->input('admin_id'));
        $email = $admin->email;
        $name = $admin->name;
        $password = substr(str_shuffle('1234567890abcdefghijklmnopqrstuvwxyz'), 0, 8);
        $password_hash = app()->make('hash')->make($password);
    
        $admin->password = $password_hash;
        // トランザクション処理
        $res = false;
        $res = DB::transaction(function () use ($admin) {
            // 登録実行
            $admin->save();
            return true;
        });
        if (!$res) {
            return redirect()->back()->with('message', 'PWの再発行に失敗しました');
        }

        // メール送信を実行
        $title = 'パスワード再設定';
        Mail::send(
            ['text' => 'emails.admin_password'],
            ['email' => $email, 'password' => $password, 'title' => $title],
            function ($m) use ($email) {
                $m->to($email)->subject('colleee管理画面');
            }
        );
        
        return redirect()->route('admins.index')->with('message', 'PWの再発行に成功しました');
    }

    /**
     * パスワード変更.
     */
    public function updatePassword(Request $request)
    {
        $this->validate(
            $request,
            [
                'password' => 'required|min:8|confirmed',
            ],
            [],
            [
                'password' => 'パスワード',
            ]
        );
        $admin = Auth::user();
        $password_hash = app()->make('hash')->make($request->password);
        $admin->password = $password_hash;
        $res = DB::transaction(function () use ($admin) {
            // 登録実行
            $admin->save();
            return true;
        });
        
        // 失敗した場合
        if (empty($res)) {
            return redirect()
                ->back()
                ->with('message', 'パスワードの編集に失敗しました');
        }
        
        return redirect()->back()->with('message', 'パスワードの編集に成功しました');
    }
}
