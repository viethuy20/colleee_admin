<?php
namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Validator;

use App\EmailBlockDomain;
use App\Http\Controllers\Controller;

/**
 * メールアドレスブロックドメイン管理コントローラー.
 */
class EmailBlockDomainsController extends Controller
{
    /**
     * メールアドレスブロックドメイン一覧.
     */
    public function index()
    {
        // メールアドレスブロックドメイン一覧を取得
        if (Auth::user()->role == \App\Admin::DRAFT_ROLE) {
            return abort(403, 'This action is unauthorized.');
        }
        $email_block_domain_list = EmailBlockDomain::orderBy('id', 'asc')->get();
        return view('email_block_domains.list', ['email_block_domain_list' => $email_block_domain_list]);
    }

    /**
     * 作成.
     */
    public function create()
    {
        if (Auth::user()->role == \App\Admin::DRAFT_ROLE) {
            return abort(403, 'This action is unauthorized.');
        }
        return $this->edit(EmailBlockDomain::getDefault());
    }

    /**
     * 更新.
     * @param EmailBlockDomain $email_block_domain メールアドレスブロックドメイン
     */
    public function edit(EmailBlockDomain $email_block_domain)
    {
        // 初期値・入力値を取得
        $email_block_domain_map = $email_block_domain->only(['id', 'domain']);
        return view('email_block_domains.edit', ['email_block_domain' => $email_block_domain_map]);
    }

    /**
     * 保存.
     */
    public function store(Request $request)
    {
        //
        $this->validate(
            $request,
            [
                'id' => ['nullable', 'integer'],
                'domain' => ['required'],
            ],
            [],
            [
                'id' => 'ID',
                'domain' => 'ドメイン'
            ]
        );
        // メールアドレスブロックドメイン情報
        $email_block_domain = $request->filled('id') ? EmailBlockDomain::find($request->input('id')) :
            EmailBlockDomain::getDefault();
        $email_block_domain->domain = $request->input('domain');

        // トランザクション処理
        $res = DB::transaction(function () use ($email_block_domain) {
            // 登録実行
            $email_block_domain->save();
            return true;
        });
        // 失敗した場合
        if (empty($res)) {
            return redirect()->back()
                ->withInput()
                ->with('message', 'メールアドレスブロックドメインの編集に失敗しました');
        }
        return redirect(route('email_block_domains.edit', ['email_block_domain' => $email_block_domain]))
            ->with('message', 'メールアドレスブロックドメインの編集に成功しました');
    }

    private function changeStatus(EmailBlockDomain $email_block_domain, bool $enable)
    {
        if ($enable) {
            $action = '有効化';
            $email_block_domain->status = 0;
        } else {
            $action = '無効化';
            $email_block_domain->status = 1;
            $email_block_domain->deleted_at = Carbon::now();
        }

        // トランザクション処理
        $res = DB::transaction(function () use ($email_block_domain) {
            // 登録実行
            $email_block_domain->save();
            return true;
        });

        // 失敗した場合
        $message = empty($res) ? 'メールアドレスブロックドメイン情報の'.$action.'に失敗しました' : 'メールアドレスブロックドメイン情報の'.$action.'に成功しました';

        return redirect()
            ->back()
            ->with('message', $message);
    }

    /**
     * 有効化.
     * @param EmailBlockDomain $email_block_domain メールアドレスブロックドメイン
     */
    public function enable(EmailBlockDomain $email_block_domain)
    {
        return $this->changeStatus($email_block_domain, true);
    }

    /**
     * 無効化.
     * @param int EmailBlockDomain $email_block_domain メールアドレスブロックドメイン
     */
    public function destroy(EmailBlockDomain $email_block_domain)
    {
        return $this->changeStatus($email_block_domain, false);
    }
}
