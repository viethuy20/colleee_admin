<?php
namespace App\Http\Controllers;

use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use App\Asp;
use App\Buser;
use App\Http\Controllers\Controller;
use App\Paginators\BasePaginator;
use App\PreAffReward;
use App\User;

/**
 * ポイント先出し成果管理コントローラー.
 */
class PreAffRewardsController extends Controller
{
    /**
     * ポイント先出し成果一覧.
     * @param User $user ユーザー
     */
    public function getList(User $user)
    {
        $paginator = BasePaginator::getDefault(
            ['page' => 1,],
            function ($params) use ($user) {
                return PreAffReward::where('user_id', '=', $user->id)
                    ->orderBy('id', 'desc');
            },
            100
        );
        $asp_map = Asp::where('status', '=', 0)
            ->pluck('name', 'id')
            ->all();
        return view('pre_aff_reward_list', ['paginator' => $paginator, 'user' => $user, 'asp_map' => $asp_map]);
    }

    public function unblock(Request $request)
    {
        //
        $this->validate(
            $request,
            [
                'user_id' => ['required', 'integer',
                    Rule::exists('busers', 'user_id')->where(function ($query) {
                        $query->where('type', '=', Buser::PRE_REWARD_TYPE)
                            ->where('status', '=', Buser::BLOCKED_STATUS);
                    }),
                ],
            ],
            [],
            ['user_id' => 'ユーザーID',]
        );

        $user_id = $request->input('user_id');
        // ブロックを解除
        Buser::unblock(Buser::PRE_REWARD_TYPE, $user_id);
        // ポイント先出し成果から損害の出たものを除外
        PreAffReward::where('user_id', '=', $user_id)
            ->where('status', '=', PreAffReward::DAMAGE_STATUS)
            ->update(['status' => PreAffReward::EXCLUDED_STATUS]);

        return redirect()->back()->with('message', 'ユーザーのポイント先出しブロックを解除しました');
    }
}
