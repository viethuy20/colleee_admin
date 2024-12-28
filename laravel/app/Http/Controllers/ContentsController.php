<?php
namespace App\Http\Controllers;

use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Validator;

use App\Attachment;
use App\Content;
use App\Http\Controllers\Controller;
use App\Spot;
use Illuminate\Support\Facades\Auth;

/**
 * 掲載欄内容管理コントローラー.
 */
class ContentsController extends Controller
{
    /**
     * 欄内容一覧.
     * @param Spot $spot 掲載欄
     */
    public function getList(Spot $spot)
    {
        if (Auth::guard('admin')->user()->role == \App\Admin::DRAFT_ROLE) {
            return abort(403, 'This action is unauthorized.');
        }
        // 掲載欄内容リスト取得
        $content_list = $spot
            ->contents()
            ->get();
        return view('contents.list', ['spot' => $spot, 'content_list' => $content_list]);
    }
    
    /**
     * 掲載欄内容情報作成.
     * @param Spot $spot 掲載欄
     */
    public function create(Spot $spot)
    {
        if (Auth::guard('admin')->user()->role == \App\Admin::DRAFT_ROLE) {
            return abort(403, 'This action is unauthorized.');
        }
        return $this->edit($spot->getDefaultContent());
    }
    
    /**
     * 掲載欄内容情報更新.
     * @param Content $content 掲載欄内容
     */
    public function edit(Content $content)
    {
        // 掲載欄内容初期値・入力値を取得
        $content_map = $content->only(['id', 'spot_id', 'title', 'img_ids', 'devices', 'priority']);
        $content_map['start_at'] = $content->start_at->format('Y-m-d H:i');
        $content_map['stop_at'] = $content->stop_at->eq(Carbon::parse('9999-12-31')->endOfMonth()) ? '' :
            $content->stop_at->format('Y-m-d H:i');
        $content_map['data'] = json_decode($content->data, true);
        
        $datas = ['spot' => $content->spot, 'content' => $content_map];

        // 画像ファイルリスト取得
        if (isset($content_map['img_ids'])) {
            $datas['image_list'] = Attachment::ofList(explode(',', $content_map['img_ids']))
                ->get();
        } else {
            $datas['image_list'] = collect();
        }
        
        return view('contents.edit', $datas);
    }
    
    /**
     * 掲載欄内容情報保存.
     * @param Request $request {@link Request}
     */
    public function store(Request $request)
    {
        $validateAttributes = [
            'id' => 'ID',
            'spot_id' => '掲載欄ID',
            'title' => 'タイトル',
            'img_ids' => '画像ID',
            'devices' => '対象デバイス',
            'priority' => '優先度',
            'start_at' => '開始日時',
            'stop_at' => '終了日時',
        ];
        //
        $this->validate(
            $request,
            [
                'id' => ['nullable', 'integer'],
                'spot_id' => ['required', 'integer'],
                'title' => ['required', 'max:256'],
                'devices' => ['required', 'integer'],
                'priority' => ['required', 'integer'],
                'start_at' => ['required', 'date_format:"Y-m-d H:i"'],
                'stop_at' => ['nullable', 'date_format:"Y-m-d H:i"'],
            ],
            [],
            $validateAttributes
        );

        // 掲載欄情報取得
        $spot = Spot::find($request->input('spot_id'));
        $spot_data = json_decode($spot->data);
        $date_rule = [];
        foreach ($spot_data as $key => $info) {
            $index = 'data.'.$key;
            $validateAttributes[$index] = $info->label;
            if (isset($info->nullable) && $info->nullable) {
                $rule_list = ['nullable'];
            } else {
                $rule_list = ['required'];
            }
            if ($info->type == 'url') {
                //$rule_list[] = 'url';
            }
            if ($info->type == 'img_url') {
                $rule_list[] = ['active_url','secure_resource'];
            }
            if ($info->type == 'string') {
                $rule_list[] = 'max:256';
            }
            if ($info->type == 'number') {
                $rule_list[] = 'numeric';
            }
            $date_rule[$index] = $rule_list;
        }
        
        $content_data = $request->only('data') ?? [];
                
        // 追加バリデーション
        $validator = Validator::make(
            $content_data,
            $date_rule,
            [],
            $validateAttributes
        );

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        // 掲載欄内容情報
        $content = null;
        
        $fill_keys = ['spot_id', 'title', 'devices', 'priority'];
        // 初期データ取得
        if ($request->filled('id')) {
            $content = Content::find($request->input('id'));
        } else {
            $content = $spot->getDefaultContent();
            // 画像IDリストを登録
            $fill_keys[] = 'img_ids';
        }
        
        // 掲載欄内容
        $content->fill($request->only($fill_keys));
        // データ
        $content->data = json_encode($content_data['data']);
        // 開始日時
        $content->start_at = Carbon::parse($request->input('start_at').':00');
        // 終了日時
        $stop_at = $request->input('stop_at');
        $content->stop_at = isset($stop_at) ? Carbon::parse($stop_at.':00') : Carbon::parse('9999-12-31')->endOfMonth();

        // トランザクション処理
        $res = DB::transaction(function () use ($content) {
            // 登録実行
            $content->save();
            return true;
        });
        // 失敗した場合
        if (empty($res)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('message', '掲載欄内容情報の編集に失敗しました');
        }
        
        return redirect(route('contents.edit', ['content' => $content]))->with('message', '掲載欄内容情報の編集に成功しました');
    }
    
    /**
     * 画像保存.
     * @param Request $request {@link Request}
     */
    public function storeImg(Request $request)
    {
        // 画像以外を取得
        $datas = $request->except('file');
        
        // 'dimensions:min_width=120,min_height=60,max_width=120,max_height=120',
        $this->validate(
            $request,
            ['file' => ['required', 'file',],],
            [],
            ['file' => '画像ファイル']
        );
        
        // アップロード画像を取得
        $file = $request->file('file');
        //
        $attachment = Attachment::getDefault();
        // 画像保存実行
        $res = $attachment->saveImg($file);
        
        // 失敗
        if (!$res) {
            return redirect()
                ->back()
                ->withInput($datas)
                ->withErrors(['file' => '画像がアップロードされていないか不正なデータです。']);
        }
        
        // IDが存在する場合、プログラム情報も更新する
        if ($request->filled('id')) {
            $content = Content::find($request->input('id'));
            $img_ids = isset($content->img_ids) ? ($content->img_ids.','.$attachment->id) : $attachment->id;
            $content->img_ids = $img_ids;
            $res = DB::transaction(function () use ($content) {
                // 保存
                $content->save();
                return true;
            });
            
            if (!$res) {
                return redirect()
                    ->back()
                    ->withInput($datas)
                    ->with('画像ファイルの作成に失敗しました');
            }
        } else {
            $img_ids = isset($datas['img_ids']) ? ($datas['img_ids'].','.$attachment->id) : $attachment->id;
        }
        
        // 画像IDを保存
        $datas['img_ids'] = $img_ids;
        
        return redirect()
            ->back()
            ->withInput($datas)
            ->with('画像ファイルを作成しました');
    }
    
    /**
     * 掲載欄内容削除.
     * @param Content $content 掲載欄内容
     */
    public function destroy(Content $content)
    {
        // 保存実行
        $content->status = 1;
        $content->deleted_at = Carbon::now();
        // トランザクション処理
        $res = DB::transaction(function () use ($content) {
            // 登録実行
            $content->save();
            return true;
        });

        return redirect()
            ->back()
            ->with('message', empty($res) ? '掲載欄内容情報の削除に失敗しました' : '掲載欄内容情報の削除に成功しました');
    }
}
