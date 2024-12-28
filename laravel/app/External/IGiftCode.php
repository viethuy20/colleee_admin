<?php
namespace App\External;

use Carbon\Carbon;

interface IGiftCode
{
    /**
     * レスポンス本文取得.
     * @return string レスポンス本文
     */
    public function getBody() :string;
    /**
     * レスポンスを解析.
     * @param string $body レスポンス本文
     * @return IGiftCode|null 解析が成功した場合はIGiftCodeを、失敗した場合はnullを返す
     */
    public static function parse(string $body) :?IGiftCode;
    /**
     * ギフトコード取得.
     * @return string|null 成功した場合はギフトコードを、失敗した場合はnullを返す
     */
    public function getGiftCode() :?string;
    /**
     * ギフトコード2取得.
     * @return string|null 成功した場合はギフトコードを、失敗した場合はnullを返す
     */
    public function getGiftCode2() :?string;
    /**
     * 管理コード取得.
     * @return string|null 成功した場合は管理コードを、失敗した場合はnullを返す
     */
    public function getManagementCode() :?string;
    /**
     * 券面額取得.
     * @return int 額面額
     */
    public function getFaceValue() :?int;
    /**
     * 有効期限取得.
     * @return Carbon|null 有効期限が存在する場合は有効期限を、存在しない場合はnullを返す
     */
    public function getExpireAt() :?Carbon;
}
