<?php
namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * クレジットカード.
 */
class CreditCard extends Model
{
    use DBTrait;
    /**
     * モデルに関連付けるデータベースのテーブルを指定.
     * @var string
     */
    protected $table = 'credit_cards';
    
    /**
     * createメソッド実行時に、入力を禁止するカラムの指定.
     * @var array
     */
    protected $guarded = ['id'];
    
    /**
     * 日付を変形する属性
     * @var array
     */
    protected $dates = ['start_at', 'stop_at', 'deleted_at'];

    protected $casts = [
        'start_at' => 'datetime',
        'stop_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    
    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id', 'id');
    }

    /**
     * ブランド取得.
     * @return array データ
     */
    public function getBrandAttribute() : array
    {
        return self::int2Array($this->brands);
    }

    /**
     * ブランド登録.
     * @param array|NULL $brand_list ブランドIDリスト
     */
    public function setBrandAttribute($brand_list)
    {
        $this->brands = isset($brand_list) ? self::array2Int($brand_list) : 0;
    }
    
    /**
     * 電子マネー取得.
     * @return array データ
     */
    public function getEmoneyAttribute() : array
    {
        return self::int2Array($this->emoneys);
    }

    /**
     * 電子マネー登録.
     * @param array|NULL $emoney_list 電子マネーIDリスト
     */
    public function setEmoneyAttribute($emoney_list)
    {
        $this->emoneys = isset($emoney_list) ? self::array2Int($emoney_list) : 0;
    }
    
    /**
     * 付帯保険取得.
     * @return array データ
     */
    public function getInsuranceAttribute() : array
    {
        return self::int2Array($this->insurances);
    }
    
    /**
     * 付帯保険登録.
     * @param array|NULL $insurance_list 付帯保険IDリスト
     */
    public function setInsuranceAttribute($insurance_list)
    {
        $this->insurances = isset($insurance_list) ? self::array2Int($insurance_list) : 0;
    }
    
    /**
     * ショップ取得.
     * @return array データ
     */
    public function getRecommendShopAttribute() : array
    {
        return isset($this->recommend_shops) ? json_decode($this->recommend_shops) : [];
    }
    
    /**
     * ショップ登録.
     * @param array|NULL $recommend_shop_list ショップリスト
     */
    public function setRecommendShopAttribute($recommend_shop_list)
    {
        $this->recommend_shops = isset($recommend_shop_list) ?
            json_encode(array_values(array_filter(array_unique($recommend_shop_list), 'strlen'))) : null;
    }
    
    /**
     * ポイント取得.
     * @return array データ
     */
    public function getPointMapAttribute() : array
    {
        $point_map = [];
        if (!isset($this->point)) {
            return $point_map;
        }
        $point_list = json_decode($this->point);
        
        if (empty($point_list)) {
            return $point_map;
        }
        foreach ($point_list as $point) {
            $point_map[$point->type] = $point->detail;
        }
        return $point_map;
    }
    
    /**
     * ポイント登録.
     * @param array|NULL $point_map ポイントマップ
     */
    public function setPointMapAttribute($point_map)
    {
        $point_list = [];
        foreach ($point_map as $type => $detail) {
            if (!isset($detail)) {
                continue;
            }
            $point_list[] = (object)['type' => $type, 'detail' => $detail];
        }
        
        $this->point = json_encode($point_list);
    }
}
