<?php
namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

use App\ExchangeInfo;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use WrapPhp;

/**
 * 交換申し込み管理コントローラー.
 */
class ExchangeInfosController extends Controller
{
    /**
     * トップ.
     */
    public function index()
    {
        if (Auth::guard('admin')->user()->role == \App\Admin::DRAFT_ROLE) {
            return abort(403, 'This action is unauthorized.');
        }
        $now = Carbon::now();
        $exchange_info_map = ExchangeInfo::where('start_at', '<=', $now)
            ->where('stop_at', '>=', $now)
            ->get()
            ->keyBy('type')
            ->all();
        return view('exchange_infos.index', ['exchange_info_map' => $exchange_info_map]);
    }

    /**
     * 参照.
     * @param int $type 種類
     */
    public function show(int $type)
    {
        $now = Carbon::now();
        $exchange_info_list = ExchangeInfo::where('type', '=', $type)
            ->orderBy('id', 'asc')
            ->get();
        return view('exchange_infos.show', ['type' => $type, 'exchange_info_list' => $exchange_info_list]);
    }

    /**
     * 作成.
     * @param int $type 種類
     */
    public function create(int $type)
    {
        return $this->edit(ExchangeInfo::getDefault($type));
    }

    /**
     * 編集.
     * @param ExchangeInfo $exchange_info 交換先情報
     */
    public function edit(ExchangeInfo $exchange_info)
    {
        $exchange_info_map = $exchange_info->only(['id', 'type', 'status', 'yen_rate', 'started', 'stopped']);
        $exchange_info_map['start_at'] = isset($exchange_info->start_at) ?
            $exchange_info->start_at->format('Y-m-d H:i') : '';
        $exchange_info_map['stop_at'] = $exchange_info->stop_at->format('Y-m-d H:i');

        $next_message_list_map = [];
        $next_message_list = $exchange_info->next_message_list;
        if (!$next_message_list->isEmpty()) {
            foreach ($next_message_list as $next_message) {
                $next_message_list_map[] = [
                    'start_at' => $next_message->start_at->format('Y-m-d H:i'),
                    'body' => $next_message->body,
                ];
            }
        }
        $next_message_total = WrapPhp::count($next_message_list_map);
        if ($next_message_total < 3) {
            $next_message_list_map = array_merge($next_message_list_map, array_fill(0, 3 - $next_message_total, null));
        }

        return view('exchange_infos.edit', [
            'exchange_info' => $exchange_info_map,
            'message_list' => $exchange_info->old_message_list,
            'next_message_list' => $next_message_list_map,
        ]);
    }

    /**
     * 交換先情報保存.
     * @param Request $request {@link Request}
     */
    public function store(Request $request)
    {
        $customAttributes = [
            'id' => 'ID',
            'type' => '種類',
            'status' => '状態',
            'yen_rate' => '円交換比率',
            'start_at' => '開始日時',
            'message.*.start_at' => 'メッセージ開始日時',
            'message.*.body' => 'メッセージ本文',
        ];

        // 初期データ取得
        if ($request->filled('id')) {
            // 交換先情報を取得
            $exchange_info = ExchangeInfo::find($request->input('id'));
            if (!isset($exchange_info->id) || $exchange_info->stopped) {
                return abort(404, 'Not Found.');
            }
        } else {
            // 種類が存在しない場合
            if (!$request->filled('type')) {
                return abort(404, 'Not Found.');
            }
            $exchange_info = ExchangeInfo::getDefault($request->input('type'));
        }

        $validateRules = [
            'id' => ['nullable', 'integer'],
            'type' => ['nullable', 'integer'],
        ];
        if (!$exchange_info->started) {
            $validateRules['status'] = ['required', 'integer'];
            $validateRules['yen_rate'] = ['required', 'integer'];
            $validateRules['start_at'] = [
                'required',
                'date_format:"Y-m-d H:i"',
                'after_or_equal:'.$exchange_info->start_at_min->format('Y-m-d H:i'),
                'before_or_equal:'.$exchange_info->start_at_max->format('Y-m-d H:i'),
            ];
        }

        //
        $this->validate(
            $request,
            $validateRules,
            [],
            $customAttributes
        );
        if (!$exchange_info->started) {
            $exchange_info->fill($request->only(['status', 'yen_rate']));
            $exchange_info->start_at = Carbon::parse($request->input('start_at').':00');
        }

        //
        $this->validate(
            $request,
            [
                'message.*.start_at' => [
                    'nullable',
                    'date_format:"Y-m-d H:i"',
                    'after_or_equal:'.$exchange_info->message_start_at_min->format('Y-m-d H:i'),
                    'before_or_equal:'.$exchange_info->message_start_at_max->format('Y-m-d H:i'),
                ],
                'message.*.body' => ['nullable'],
            ],
            [],
            $customAttributes
        );

        $messages = $request->input('message');
        $next_message_list = [];
        foreach ($messages as $message) {
            if (!isset($message['start_at'])) {
                continue;
            }
            $next_message_list[] = (object) [
                'start_at' => Carbon::parse($message['start_at'].':00'),
                'body' => $message['body'],
            ];
        }
        $exchange_info->next_message_list = collect($next_message_list);

        // 失敗した場合
        if (!$exchange_info->saveExchangeInfo()) {
            return redirect()
                ->back()
                ->withInput()
                ->with('message', '交換先情報の編集に失敗しました');
        }

        return redirect(route('exchange_infos.show', ['type' => $exchange_info->type]))
            ->with('message', '交換先情報の編集に成功しました');
    }
}
