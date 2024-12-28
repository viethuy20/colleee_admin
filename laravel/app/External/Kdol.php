<?php
namespace App\External;

use GuzzleHttp\Client;
use App\ExchangeRequestCashbackKey;
use App\ExchangeAccountUserKey;
use App\ExchangeRequest;
use App\Services\CommonService;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Carbon\Carbon;

class Kdol
{

    private $encrypt_code;
    private $client;
    private $commonService;
    private $status_code;
    private $error_code;
    private $errorMessage;
    private $response_code;
    private $data_status_code;
    private $body;
    private $transaction_time;

    public function __construct(
        CommonService $commonService
    )
    {
        $this->commonService = $commonService;
        $this->encrypt_code = config('kdol.encrypt_code');
        $this->response_code = config('kdol.response_code');

        $this->client = new Client([
            'base_uri' => config('kdol.base_uri')
        ]);

    }

    /**
     * データ整合性確保のためのデータを暗号化
     * @param string $hash_data 暗号化するデータ
     */
    public function getEncrypt($hash_data='')
    {
        $encrypt_code = config('kdol.encrypt_code');//暗号化キー

        return md5($hash_data.$encrypt_code);
    }

    public function checkHash($in,$hash){

        if(self::getEncrypt($in) == $hash){
            return true;
        }
        return false;
    }

    public function generateAuthorizationHeader(){
        $headers = [
            'content-type' => 'application/json',
        ];
        return $headers;
    }

    public function decodeUserAuth($encodedString)
    {
        $key = new Key(config('kdol.encrypt_code'), 'HS256');
        return (array) JWT::decode($encodedString, $key);
    }

    public function encodeUserAuth($data)
    {
        $key = config('kdol.encrypt_code');
        return JWT::encode($data, $key, 'HS256');
    }

    public function checkAccountResponse($response){
        if(!$response['gmo_id'] || !$response['kdol_id'] || !$response['hash'] || $response['insert_type']==='' || $response['status']===''){
           return false;
        }else{
            if(self::checkHash($response['gmo_id'].$response['insert_type'].$response['kdol_id'].$response['status'], $response['hash'])){
                return true;
            }
        }
        return false;
    }

    /**
     * Kdolのユーザ連携用のURLを生成
     */
    public function createKdolKeyUrl($user_id,$insert_type=0){

        $requesturl = config('kdol.api_url.proc_get_gmo_nikko');

        $gmo_id = $this->commonService->createExchangeAccountUserKey($user_id,ExchangeRequest::KDOL_TYPE);

        if(!$gmo_id){
            return false;
        }

        $request = [
            'gmo_id' => $gmo_id,
            'insert_type' => $insert_type,
            'redirectUrl'=> config('kdol.redirect_url'),
            'hash' => self::getEncrypt($gmo_id.$insert_type),
        ];
        
        return config('kdol.base_uri').$requesturl.'?'.http_build_query($request);
    }


    public function checkUserKey($user_id,$kdol_key){
        $this->response_reset();

        $requesturl = config('kdol.api_url.proc_get_gmo_nikko_status');
        
        $headers = $this->generateAuthorizationHeader();
        $user_key = $this->commonService->getExchangeAccountUserKey($user_id,ExchangeRequest::KDOL_TYPE);

        if(empty($user_key)){
            return false;
        }
        $kdol_id = openssl_decrypt(hex2bin($kdol_key), 'BF-ECB',config('kdol.encrypt_code'), OPENSSL_RAW_DATA | OPENSSL_DONT_ZERO_PAD_KEY );

        $request = [
            'headers' => $headers,
            'http_errors' => false,
            'query' => [
                'gmo_id' => $user_key,
                'kdol_id' => $kdol_key,
                'hash' => self::getEncrypt($user_key.$kdol_id),
            ],
        ];

        try {
            $response = $this->client->get($requesturl, $request);
            $responseData = json_decode($response->getBody()->getContents(), true);

        } catch (\Exception $e) {
            echo 'Error: ' . $e->getMessage();
            return false;
        }

        $this->status_code = $responseData['status'];
        $this->commonService->api_log(['user_id'=>$user_id,'exchange_request_id'=>0,'type'=>ExchangeRequest::KDOL_TYPE,'request'=>$request,'response'=>$responseData,'api_name'=>$requesturl,'status_code'=>$this->status_code]);
        
        if($responseData['status'] === 0){
            if(!self::checkHash($responseData['gmo_id'].$responseData['kdol_id'].$responseData['code'].$responseData['status'], $responseData['hash'])){
                return false;
            }

            if($responseData['code'] === 1){
                return true;
            }elseif($responseData['code'] === 0){
                return false;
            }
        }else{
            $this->error_code = $responseData['status'];
            $this->errorMessage = $this->response_code[$this->status_code];
            return false;
        }
        
    }

    public function cashbackPointRegist($exchange_request){
        $this->response_reset();
        
        $requesturl = config('kdol.api_url.proc_get_gmo_nikko_cashback_point');

        $kdol_id = $this->commonService->getKdolUserKey($exchange_request->user_id,ExchangeRequest::KDOL_TYPE);
        $transaction_id =$this->commonService->createExchangeRequestId($exchange_request);
        $gmo_id = $this->commonService->getExchangeAccountUserKey($exchange_request->user_id,ExchangeRequest::KDOL_TYPE);
        $this->transaction_time = $transaction_time = Carbon::now()->format('U');
        if(!$kdol_id || !$transaction_id || !$gmo_id){
            return false;
        }

        $requestBody = [
            'gmo_id'=> $gmo_id,
            'kdol_id'=> $kdol_id,
            'point'=>  $exchange_request->point,
            'transaction_id'=> $transaction_id,
            'transaction_time'=> $transaction_time,
            'hash'=>self::getEncrypt($gmo_id.$kdol_id.$exchange_request->point.$transaction_id.$transaction_time),
        ];
        
        $headers = $this->generateAuthorizationHeader();

        $request = [
            'headers' => $headers,
            'http_errors' => false,
            'query' => $requestBody,
        ];

        try {
            $response = $this->client->get($requesturl, $request);
            $this->body = $responseData = json_decode($response->getBody()->getContents(), true);

        } catch (\Exception $e) {
            echo 'Error: ' . $e->getMessage();
            return false;
        }

        $this->commonService->api_log(['user_id'=>$exchange_request->user_id,'exchange_request_id'=>$exchange_request->id,'type'=>ExchangeRequest::KDOL_TYPE,'request'=>$request,'response'=>$responseData,'api_name'=>$requesturl,'status_code'=>$responseData['status']]);

        

        $this->status_code = $responseData['status'];
        if($responseData['status'] == '0'){

            //hashの検証
            if(!self::checkHash($responseData['gmo_id'].$responseData['kdol_id'].$responseData['point'].$responseData['transaction_id'].$responseData['transaction_time'].$responseData['status'], $responseData['hash'])){
                return false;
            }
           return true;

        }else{
            $this->error_code = $responseData['status'];
            $this->errorMessage = $this->response_code[$this->status_code];
            return false;
        }
        
    }

    public function checkCashbackStatus($exchange_request){
        $this->response_reset();


        $requesturl = config('kdol.api_url.proc_get_gmo_nikko_cashback_ref');
        
        $headers = $this->generateAuthorizationHeader();
        
        $kdol_id = $this->commonService->getKdolUserKey($exchange_request->user_id,ExchangeRequest::KDOL_TYPE);
        $transaction_id = $this->commonService->getExchangeRequestCashbackKey($exchange_request->id);
        $gmo_id = $this->commonService->getExchangeAccountUserKey($exchange_request->user_id,ExchangeRequest::KDOL_TYPE);
        if(!$kdol_id || !$transaction_id || !$gmo_id){
            return false;
        }
        

        $request = [
            'headers' => $headers,
            'http_errors' => false,
            'query' => [
                'gmo_id' => $gmo_id,
                'kdol_id' => $kdol_id,
                'transaction_id' => $transaction_id,
                'hash' => self::getEncrypt($gmo_id.$kdol_id.$transaction_id),
            ],
        ];

        try {
            $response = $this->client->get($requesturl, $request);
            $this->body = $responseData = json_decode($response->getBody()->getContents(), true);

        } catch (\Exception $e) {
            echo 'Error: ' . $e->getMessage();
            return false;
        }

        $this->status_code = $responseData['status'];
        $this->commonService->api_log(['user_id'=>$exchange_request->user_id,'exchange_request_id'=>$exchange_request->id,'type'=>ExchangeRequest::KDOL_TYPE,'request'=>$request,'response'=>$responseData,'api_name'=>$requesturl,'status_code'=>$this->status_code]);
        
        

        if($responseData['status'] === 0){
            $this->data_status_code = $responseData['code'];
            //hashの検証

            if(!self::checkHash($responseData['gmo_id'].$responseData['kdol_id'].$responseData['transaction_id'].$responseData['code'].$responseData['status'], $responseData['hash'])){
                return false;
            }
            
            if((int)$responseData['code'] === 1){//ACCEPTED
                return true;
             }elseif((int)$responseData['code'] === 2){//SUCCESS
                 return true;
             }elseif((int)$responseData['code'] === 3){//FAILURE
                return false;
            }
        
        }else{
            $this->error_code = $responseData['status'];
            $this->errorMessage = $this->response_code[$this->status_code];
            return false;
        }
    }

    public function response_reset(){
        $this->body = null;
        $this->status_code = null;
        $this->data_status_code = null;
        $this->error_code = null;
        $this->errorMessage = null;
        $this->transaction_time = null;
    }

    /**
     * エラーコード取得.
     * @return string エラーコード
     */
    public function getErrorCode()
    {
        return $this->error_code;
    }

    /**
     * エラーメッセージ取得.
     * @return string エラーメッセージ
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * 結果取得.
     * @return string 結果
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * リクエスト取得.
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * レスポンス取得.
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * ステータスコード取得.
     */
    public function getStatusCode()
    {
        return $this->status_code;
    }

    /**
     * ステータスコード取得.
     */
    public function getDataStatusCode()
    {
        return $this->data_status_code;
    }

    public function getTransactionTime(){
        return $this->transaction_time;
    }

    public function getResponseCode(){
        $status_code = $this->getStatusCode();
        $error_code = $this->getErrorCode();
        $error_message = $this->getErrorMessage();
        $data_status_code = $this->getDataStatusCode();
        $response_code = $error_code ? $error_code . ' : ' . $error_message : $status_code . ':' . $data_status_code;
        return $response_code;
    }

}