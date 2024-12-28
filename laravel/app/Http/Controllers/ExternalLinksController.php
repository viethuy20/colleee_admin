<?php
namespace App\Http\Controllers;

use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

use App\Asp;
use App\ExternalLink;
use App\Http\Controllers\Controller;
use App\Paginators\BasePaginator;
use App\User;
use Illuminate\Support\Facades\Auth;

/**
 * クリック一覧コントローラー.
 */
class ExternalLinksController extends Controller
{
    /**
     * Viewデータ取得.
     * @return array データ
     */
    private function getBaseDatas() : array
    {

        return ['asp_map' => $asp_map];
    }
    

    
    /**
     * クリック一覧検索.
     */
    public function index()
    {
        if (Auth::guard('admin')->user()->role == \App\Admin::DRAFT_ROLE) {
            return abort(403, 'This action is unauthorized.');
        }
        $paginator = BasePaginator::getDefault(
            [
                'page' => 1, 'user_name' => null, 'asp_id' => null, 'asp_affiliate_id' => null,
                'program_id' => null, 'title' => null, 'ip' => null, 'start_at' => null, 'end_at' => null,
            ],
            function ($params) {
                $builder = ExternalLink::select('external_links.*');
                // ユーザーID検索
                if (isset($params['user_name'])) {
                    $user_id = User::getIdByName($params['user_name']);
                    $builder = (!empty($user_id)) ? $builder->where('user_id', '=', $user_id) :
                        $builder->where(DB::raw('1 = 0'));
                }

                // ASP
                $builder = isset($params['asp_id']) ? $builder->where('asp_id', '=', $params['asp_id']) : $builder;

                // ASP側広告ID
                $builder = isset($params['asp_affiliate_id']) ?
                    $builder->where('asp_affiliate_id', '=', $params['asp_affiliate_id']) : $builder;
        
                // プログラムID
                $builder = isset($params['program_id']) ?
                    $builder->where('program_id', '=', $params['program_id']) : $builder;
        
                // プログラム名検索
                if (isset($params['title'])) {
                    $builder = $builder->whereIn('program_id', function ($query) use ($params) {
                        $query->select('id')
                            ->from('programs')
                            ->whereRaw(
                                'title COLLATE utf8mb4_unicode_ci LIKE ?',
                                ['%'.addcslashes($params['title'], '\_%').'%']
                            );
                    });
                }

                // IP
                $builder = isset($params['ip']) ? $builder->where('ip', '=', $params['ip']) : $builder;

                // 日時
                if (isset($params['start_at'])) {
                    try {
                        $start_at = Carbon::parse($params['start_at']);
                        $builder = $builder->where('created_at', '>=', $start_at);
                    } catch (\Exception $e) {
                        $builder = $builder->where(DB::raw('1 = 0'));
                    }
                }
                if (isset($params['end_at'])) {
                    try {
                        $end_at = Carbon::parse($params['end_at']);
                        $builder = $builder->where('created_at', '<=', $end_at);
                    } catch (\Exception $e) {
                        $builder = $builder->where(DB::raw('1 = 0'));
                    }
                }

                $builder = $builder->orderBy('id', 'desc');

                return $builder;
            },
            50
        );

        // ASPマスタ取得
        $asp_map = Asp::where('status', '=', 0)
            ->pluck('name', 'id')
            ->all();
        
        return view('external_link_index', ['paginator' => $paginator, 'asp_map' => $asp_map]);
    }
}
