<?php
namespace App\Http\Controllers;

use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Validator;

use App\PopupAds;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Program;

/**
 * 掲載欄内容管理コントローラー.
 */
class PopupAdsController extends Controller
{

    public function index()
    {
        if (Auth::guard('admin')->user()->role == \App\Admin::DRAFT_ROLE) {
            return abort(403, 'This action is unauthorized.');
        }
        $paginator = PopupAds::orderBy('id', 'desc')->paginate(20);
        return view('popup_ads.index', ['paginator' => $paginator]);
    }

    public function create()
    {
        if (Auth::guard('admin')->user()->role == \App\Admin::DRAFT_ROLE) {
            return abort(403, 'This action is unauthorized.');
        }
        return $this->edit(PopupAds::getDefault());
    }
    
    public function edit(PopupAds $popup_ad)
    {
        $popupads_map = $popup_ad->only(['id', 'devices', 'program_id', 'title', 'priority', 'start_at', 'stop_at']);
        $start_at = $popup_ad->start_at->format('Y-m-d H:i');
        $start_at_list =  explode(' ',$start_at);
        $popupads_map['start_at_date'] = $start_at_list[0];
        $popupads_map['start_at_time'] = $start_at_list[1];
        $stop_at = $popup_ad->stop_at->format('Y-m-d H:i');
        $stop_at_list =  explode(' ',$stop_at);
        $popupads_map['stop_at_date'] = $stop_at_list[0];
        $popupads_map['stop_at_time'] = $stop_at_list[1];
        $datas = ['ads' => $popupads_map,];
        return view('popup_ads.edit', $datas);
    }

    public function store(Request $request)
    {
        $validateAttributes = [
            'id' => 'ID',
            'title' => 'タイトル',
            'devices' => '対象デバイス',
            'priority' => '優先度',
            'program_id' => 'プログラムID',
            'start_at_date' => '開始日付',
            'start_at_time' => '開始時刻',
            'stop_at_date' => '終了日付',
            'stop_at_time' => '終了時刻',
        ];
        
        $rules = [
            'id' => ['nullable', 'integer'],
            'title' => ['required', 'max:256'],
            'devices' => ['required', 'integer'],
            'program_id' => ['required', 'integer'],
            'priority' => ['required', 'integer', 'between:0,999'],
            'start_at_date' => ['required', 'date_format:"Y-m-d"'],
            'start_at_time' => ['required', 'date_format:"H:i"'],
            'stop_at_date' => ['required', 'date_format:"Y-m-d"'],
            'stop_at_time' => ['required', 'date_format:"H:i"'],
            'start_at' => 'before:stop_at'
        ];
        
        $messages = [
            'start_at.before' => '開始日時は終了日時以前の日付にしてください'
        ];
        
        $request->merge([
            'start_at' => $request['start_at_date'] . ' ' . $request['start_at_time'],
            'stop_at' => $request['stop_at_date'] . ' ' . $request['stop_at_time']
        ]);
        
        $validator = Validator::make($request->all(), $rules, $messages, $validateAttributes);
        
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        if ($request->has('id')) {
            $popup_ads = PopupAds::find($request->input('id'));

        } else {
            $popup_ads = PopupAds::getDefault();
        }
        $popup_ads->fill($request->only(['title', 'devices', 'priority', 'program_id']));
        // 開始日時
        $start_at = $request['start_at_date'].' '.$request['start_at_time'];
        $popup_ads->start_at = Carbon::parse($start_at.':00');
        // 終了日時
        $stop_at = $request['stop_at_date'].' '.$request['stop_at_time'];
        $popup_ads->stop_at = Carbon::parse($stop_at.':00');
        // トランザクション処理
        $res = DB::transaction(function () use ($popup_ads) {
            // 登録実行
            $popup_ads->save();
            return true;
        });
        // 失敗した場合
        if (empty($res)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('message', 'エラー');
        }
        
        return redirect(route('popup_ads.index'));
    }
    

    public function destroy(PopupAds $popup_ad) 
    {
        // 保存実行
        $res = DB::transaction(function () use ($popup_ad) {
            // 保存実行
            $popup_ad->delete();
            return true;
        });
        
        return redirect()
            ->back()
            ->with('message', empty($res) ? '削除に失敗しました' : '正常に削除されました');
    }

    public function ajaxGetProgram(Request $request)
    {
        $program_id = $request->input('id');
        $program = Program::where('id', $program_id)->first();
        if (empty($program)) {
            return response()->json([
                'url' => '',
                'error' => true
            ], 200);
        }

        return response()->json([
            'title' => $program->title,
            'error' => false
        ], 200);

    }
}
