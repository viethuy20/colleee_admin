<?php
namespace App\Http\Controllers;

use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use App\CreditCard;
use App\Http\Controllers\Controller;
use App\Paginators\BasePaginator;
use App\Program;

/**
 * クレジットカード管理コントローラー.
 */
class CreditCardsController extends Controller
{
    /**
     * クレジットカード検索.
     */
    public function index()
    {
        $paginator = BasePaginator::getDefault(
            [
                'page' => 1, 'sort' => 0, 'sort_reverse' => 0, 'program_id' => null, 'title' => null,
                'brand' => null, 'annual_free' => null, 'emoney' => null, 'etc' => null, 'apple_pay' => null,
                'insurance' => null, 'start_at' => null, 'stop_at' => null
            ],
            function ($params) {
                $builder = CreditCard::select('credit_cards.*');

                // 件名検索
                if (isset($params['title'])) {
                    $builder = $builder->whereRaw(
                        '`title` COLLATE utf8mb4_unicode_ci LIKE ?',
                        ['%'.addcslashes($params['title'], '\_%').'%']
                    );
                }
                // プログラムID検索
                $builder = isset($params['program_id']) ?
                    $builder->where('program_id', '=', $params['program_id']) : $builder;
                // 年会費
                $builder = isset($params['annual_free']) ?
                    $builder->where('annual_free', '=', $params['annual_free']) : $builder;
                // ETC
                $builder = isset($params['etc']) ? $builder->where('etc', '=', $params['etc']) : $builder;
                // ApplePay
                $builder = isset($params['apple_pay']) ?
                    $builder->where('apple_pay', '=', $params['apple_pay']) : $builder;
                // ブランド
                if (!empty($params['brand'])) {
                    $brand_mask = 1 << ($params['brand'] - 1);
                    $builder = $builder->whereRaw('brands & ? > 0', [$brand_mask]);
                }
                // 電子マネー
                if (!empty($params['emoney'])) {
                    $category_mask = 1 << ($params['emoney'] - 1);
                    $builder = $builder->whereRaw('emoneys & ? > 0', [$category_mask]);
                }
                // 付帯保険
                if (!empty($params['insurance'])) {
                    $category_mask = 1 << ($params['insurance'] - 1);
                    $builder = $builder->whereRaw('insurances & ? > 0', [$category_mask]);
                }
                // 開始日
                if (isset($params['start_at'])) {
                    try {
                        $start_at = Carbon::parse($params['start_at']);
                        $builder = $builder->whereBetween('start_at', [$start_at->copy()->startOfDay(),
                            $start_at->copy()->endOfDay()]);
                    } catch (\Exception $e) {
                        $builder = $builder->where(DB::raw('1 = 0'));
                    }
                }
                // 終了日
                if (isset($params['stop_at'])) {
                    try {
                        $stop_at = Carbon::parse($params['stop_at']);
                        $builder = $builder->whereBetween('stop_at', [$stop_at->copy()->startOfDay(),
                            $stop_at->copy()->endOfDay()]);
                    } catch (\Exception $e) {
                        $builder = $builder->where(DB::raw('1 = 0'));
                    }
                }

                $builder = $builder->orderBy('id', 'desc');

                return $builder;
            },
            20
        );

        return view('credit_cards.index', ['paginator' => $paginator]);
    }
    
    /**
     * クレジットカード情報更新.
     * @param Program $program プログラム
     */
    public function edit(Program $program)
    {
        // クレジットカード情報
        $credit_card = $program->credit_card;
        
        // クレジットカード初期値・入力値を取得
        $credit_card_map = $credit_card->only(['id', 'title', 'program_id', 'img_url',
            'detail', 'point_map', 'campaign', 'annual_free', 'annual_detail', 'back',
            'etc', 'etc_detail', 'apple_pay', 'start_at', 'stop_at', 'program',
            'brand', 'emoney', 'insurance', 'recommend_shop']);
        $credit_card_map['start_at'] = $credit_card->start_at->format('Y-m-d H:i');
        $credit_card_map['stop_at'] = $credit_card->stop_at->format('Y-m-d H:i');

        return view('credit_cards.edit', ['credit_card' => $credit_card_map]);
    }
    
    /**
     * クレジットカード情報保存.
     * @param Request $request {@link Request}
     */
    public function store(Request $request)
    {
        $brand_keys = array_keys(config('map.credit_card_brand'));
        $emoney_keys = array_keys(config('map.credit_card_emoney'));
        $insurance_keys = array_keys(config('map.credit_card_insurance'));

        //
        $this->validate(
            $request,
            [
                'title' => ['required', 'max:256',],
                'program_id' => ['required', 'integer', Rule::exists('programs', 'id'),],
                'img_url' => ['required', 'url', 'secure_resource',],
                'detail' => ['required',],
                'point_detail.*' => ['nullable', 'max:40',],
                'campaign' => ['nullable', 'max:400',],
                'brand.*' => ['required', 'integer', 'in:'. implode(',', $brand_keys),],
                'annual_free' => ['nullable', 'integer', 'in:1',],
                'annual_detail' => ['required_unless:annual_free,1', 'max:30',],
                'back' => ['required', 'max:20',],
                'emoney.*' => ['nullable', 'integer', 'in:'. implode(',', $emoney_keys),],
                'etc' => ['nullable', 'integer', 'in:1',],
                'etc_detail' => ['required_if:etc,1', 'max:30',],
                'apple_pay' => ['required', 'integer'],
                'insurance.*' => ['nullable', 'integer', 'in:'. implode(',', $insurance_keys),],
                'recommend_shop.*' => ['nullable', 'integer',
                    Rule::exists('programs', 'id')->where(function ($query) {
                        $query->where('status', '=', 0)
                            ->where('stop_at', '>', Carbon::now());
                    }),
                ],
                'start_at' => ['required', 'date_format:"Y-m-d H:i"',],
                'stop_at' => ['required', 'date_format:"Y-m-d H:i"',],
            ],
            [],
            [
                'title' => '案件名',
                'program_id' => 'プログラム',
                'img_url' => '画像URL',
                'detail' => '詳細',
                'point_detail.*' => '共通ポイント詳細',
                'campaign' => 'キャンペーン情報',
                'brand.*' => 'ブランド',
                'annual_free' => '永年無料',
                'annual_detail' => '年会費詳細',
                'back' => 'ポイント還元率',
                'emoney.*' => '電子マネー',
                'etc' => 'ETC付き',
                'etc_detail' => 'ETC詳細',
                'apple_pay' => 'ApplePay',
                'insurance.*' => '付帯保険',
                'recommend_shop.*' => 'おすすめショップ情報',
                'start_at' => '掲載開始日時',
                'stop_at' => '掲載終了日時',
            ]
        );

        // クレジットカード情報
        $credit_card = Program::find($request->input('program_id'))->credit_card;

        // クレジットカード
        $credit_card->fill($request->only(['title', 'img_url', 'detail', 'campaign',
            'annual_free', 'annual_detail', 'back', 'etc', 'etc_detail', 'apple_pay',]));
        $credit_card->brand = $request->input('brand');
        $credit_card->emoney = $request->input('emoney');
        $credit_card->insurance = $request->input('insurance');
        $credit_card->recommend_shop = $request->input('recommend_shop');
        $credit_card->point_map = $request->input('point_map');
        // 開始日時
        $credit_card->start_at = Carbon::parse($request->input('start_at').':00');
        // 終了日時
        $credit_card->stop_at = Carbon::parse($request->input('stop_at').':00');

        // トランザクション処理
        $res = DB::transaction(function () use ($credit_card) {
            // 登録実行
            $credit_card->save();
            return true;
        });
        
        // 失敗した場合
        if (empty($res)) {
            return redirect()->back()
                ->withInput()
                ->with('message', 'クレジットカード情報の編集に失敗しました');
        }
        
        return redirect(route('credit_cards.edit', ['program' => $credit_card->program]))
            ->with('message', 'クレジットカード情報の編集に成功しました');
    }

    private function changeStatus(CreditCard $credit_card, bool $enable)
    {
        if ($enable) {
            $action = '公開';
            $credit_card->status = 0;
        } else {
            $action = '非公開';
            $credit_card->status = 1;
            $credit_card->deleted_at = Carbon::now();
        }

        // トランザクション処理
        $res = DB::transaction(function () use ($credit_card) {
            // 登録実行
            $credit_card->save();
            return true;
        });

        // 失敗した場合
        $message = empty($res) ? 'クレジットカード情報の'.$action.'に失敗しました' : 'クレジットカード情報の'.$action.'に成功しました';

        return redirect()
            ->back()
            ->with('message', $message);
    }

    /**
     * クレジットカード公開.
     * @param Program $program プログラム
     */
    public function enable(Program $program)
    {
        return $this->changeStatus($program->credit_card, true);
    }
    
    /**
     * クレジットカード非公開.
     * @param Program $program プログラム
     */
    public function destroy(Program $program)
    {
        return $this->changeStatus($program->credit_card, false);
    }
}
