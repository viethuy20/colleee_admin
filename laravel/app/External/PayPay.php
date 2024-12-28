<?php
namespace App\External;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\Each;
use App\ExchangeAccounts;
use App\ExchangeRequest;
use App\PaypayLogs;
use GuzzleHttp\Exception\ConnectException;
use App\ExchangeRequestCashbackKey;


class PayPay
{
    private $client;
    private $error;
    private $api_key;
    private $paypay_secret;
    private $paypay_merchant_id;
    private $status_code = null;
    private $data_status_code = null;
    private $merchantCashbackId = null;
    private $error_code = null;
    private $error_code_id = null;
    private $body = null;
    private $request = null;
    private $response = null;
    private $errorMessage = null;
    private $connect_exception = false;

    public function __construct()
    {

        $this->client = new Client([
            'base_uri' => config('paypay.paypay_base_uri')
        ]);

    }

    public function getExchangeRequestId($cashback_id){
        $exchange_request_cashback_key = ExchangeRequestCashbackKey::where('cashback_id', $cashback_id)->first();
        if($exchange_request_cashback_key){
            return $exchange_request_cashback_key->exchange_request_id;
        }
        return false;
    }

    public function getCashbackId($exchange_request_id){
        $exchange_request_cashback_key = ExchangeRequestCashbackKey::where('exchange_request_id', $exchange_request_id)->first();
        if($exchange_request_cashback_key){
            return $exchange_request_cashback_key->cashback_id;
        }
        return false;
    }

    public function createExchangeRequestId($exchange_request){

        $cashback_id = $this->getCashbackId($exchange_request->id);

        if($cashback_id === false){

            $cashback_id = substr(bin2hex(random_bytes(40)),0,36);

            $count = 0;
            while(ExchangeRequestCashbackKey::where('cashback_id', $cashback_id)->exists()){
                $cashback_id = substr(bin2hex(random_bytes(40)),0,36);
                $count++;
                if($count > 10){
                    return false;
                }
            }
    
            ExchangeRequestCashbackKey::create([
                'cashback_id' => $cashback_id,
                'exchange_request_id' => $exchange_request->id,
                'user_id' => $exchange_request->user_id,
            ]);
    
            $exchange_request_cashback_key = ExchangeRequestCashbackKey::where('exchange_request_id', $exchange_request->id)->first();
            if($exchange_request_cashback_key){
                return $exchange_request_cashback_key->cashback_id;
            }else{
                return false;
            }

        }
        return $cashback_id;

    }

    /**
     * PayPayAPIのheaderを作成
     */
    private function generatePayPayAuthorizationHeader($requestBody,$requesturl,$nonce,$request_method = 'POST')
    {

        $api_key = config('paypay.paypay_api_key');
        $paypay_secret = config('paypay.paypay_secret');
        $epoch = date_timestamp_get(date_create());//現在のエポックタイムスタンプ
        $content_type = 'application/json';


        if($request_method == 'POST'){
            $hash = hash('md5',$content_type.$requestBody,true);//ok
            $hash = base64_encode($hash);//ok
        }else{
            $content_type = $hash = "empty";
        }
        
        
        $DELIMITER = "\n";
        $hmacData =  $requesturl.$DELIMITER.$request_method.$DELIMITER.$nonce.$DELIMITER.$epoch.$DELIMITER.$content_type.$DELIMITER.$hash;
        
        $signature = base64_encode(hash_hmac('sha256', $hmacData, $paypay_secret, true));
        $authHeader = "hmac OPA-Auth:" . $api_key . ":" . $signature . ":" . $nonce . ":" . $epoch . ":" . $hash;
        
        $headers = [
            'content-type' => $content_type,
            'Authorization' => $authHeader,
            'X-ASSUME-MERCHANT' => config('paypay.paypay_merchant_id'),
        ];

        return $headers;
    }

    /**
     * PayPayのユーザー認証チェック
     */
    public function checkUser($paypayUserId){
        $requesturl = '/v2/user/authorizations';
        $nonce = substr(bin2hex(random_bytes(16)),0,8);
        $requestBody = '';
        //$requestBody = json_encode($requestBody);
        $headers = $this->generatePayPayAuthorizationHeader($requestBody,$requesturl,$nonce,'GET');

        $request = [
            'headers' => $headers,
            'http_errors' => false,
            'timeout' => 15,
            'query' => [
            'userAuthorizationId' => $paypayUserId,
            ],
        ];

        try {
            $response = $this->client->get($requesturl, $request);
            $responseData = json_decode($response->getBody()->getContents(), true);

        } catch (\Exception $e) {
            echo 'Error: ' . $e->getMessage();
            return false;
        }
        

        $this->status_code = $responseData['resultInfo']['code'];
        if($responseData['resultInfo']['code'] == 'SUCCESS' && $responseData['data']['status'] == 'ACTIVE'){
           return true;
        }else{
            $this->error_code = $responseData['resultInfo']['code'];
            $this->error_code_id = $responseData['resultInfo']['codeId'];
            $this->errorMessage = $responseData['resultInfo']['message'];
            //ログを残す
            $this->paypay_log(null,$paypayUserId,0,$requesturl,$request,$responseData);
            return false;
        }
    }


    /**
     * ユーザーの残高の付与をrequestする
     * このAPIでは残高付与の受付のみを行い、
     * PayPay側の非同期処理にて付与を実施しています。
     */
    public function execute($paypayUserId,$amount,$exchange_request_id,$cashback_id){

        $this->response_reset();//変数初期化

        $requesturl = '/v2/cashback';
        $nonce = substr(bin2hex(random_bytes(16)),0,8);
        $this->merchantCashbackId = $exchange_request_id;

        $requestBody = [
            'userAuthorizationId'=> $paypayUserId,
            'merchantCashbackId'=> $cashback_id,
            'amount'=>  ['amount'=> $amount, 'currency'=> 'JPY'],
            'requestedAt'=> date_timestamp_get(date_create()),
        ];
        $requestBody = json_encode($requestBody);
        $headers = $this->generatePayPayAuthorizationHeader($requestBody,$requesturl,$nonce);

        $request = [
            'headers' => $headers,
            'http_errors' => false,
            'timeout' => 30,
            'body' => $requestBody,
        ];

            
        try {
            
            $response = $this->client->post($requesturl, $request);
            $responseData = json_decode($response->getBody()->getContents(), true);
            $this->body = json_decode($response->getBody(), true);
            
        }catch(ConnectException $e) {
            $errno = $e->getHandlerContext()['errno'];
            $error_message = $e->getHandlerContext()['error'];
            if($errno == 28){//CURLE_OPERATION_TIMEDOUT
                $this->connect_exception = true;
            }
            $this->error_code = $errno;
            $this->errorMessage = $error_message;
            //ログを残す
            $this->paypay_log(null,$paypayUserId,$exchange_request_id,$requesturl,$request,['error_code'=>$this->error_code,'error_message'=>$this->errorMessage]);
            \Log::error('Error: ' . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            $errno = $e->getHandlerContext()['errno'];
            $error_message = $e->getHandlerContext()['error'];
            $this->error_code = $errno;
            $this->errorMessage = $error_message;
            //ログを残す
            $this->paypay_log(null,$paypayUserId,$exchange_request_id,$requesturl,$request,['error_code'=>$this->error_code,'error_message'=>$this->errorMessage]);
            \Log::error('Error: ' . $e->getMessage());
            return false;
        }
        $this->status_code = $code = $responseData['resultInfo']['code'];

        //ログを残す
        $this->paypay_log(null,$paypayUserId,$exchange_request_id,$requesturl,$request,$responseData);

        if($code == 'REQUEST_ACCEPTED' || $code == 'SUCCESS'){
           return true;
        }else{
            // エラー時の処理
            $this->error_code = $responseData['resultInfo']['code'];
            $this->error_code_id = $responseData['resultInfo']['codeId'];
            $this->errorMessage = $responseData['resultInfo']['message'];
            //\Log::error("PayPay Error: Code: {$this->error_code}, Message: {$this->errorMessage}");
            return false;
        }
    }

    /**
     * PayPayの付与されたCashbackのトランザクションの状態を参照
     */
    public function checkCashbackDetails($exchange_request_id,$user_id){

        $this->response_reset();//変数初期化

        $merchantCashbackId = $this->getCashbackId($exchange_request_id);
        if(!$merchantCashbackId){
            return false;
        }

        $requesturl = '/v2/cashback';
        $requesturl_full = $requesturl.'/'.$merchantCashbackId;
        $nonce = substr(bin2hex(random_bytes(16)),0,8);

        $requestBody = '';
        $headers = $this->generatePayPayAuthorizationHeader($requestBody,$requesturl_full,$nonce,'GET');

        try {
            $response = $this->client->get($requesturl_full, [
                'headers' => $headers,
                'timeout' => 10,
                'http_errors' => false,
            ]);
            $responseData = json_decode($response->getBody()->getContents(), true);
            $this->body = json_decode($response->getBody(), true);
           

        } catch (\Exception $e) {
            echo 'Error: ' . $e->getMessage();
            //\Log::error('Error: ' . $e->getMessage());
           return false;
        }

        $this->status_code = $responseData['resultInfo']['code'];

        //ログを残す
        $this->paypay_log($user_id,0,$exchange_request_id,$requesturl_full,[$merchantCashbackId],$responseData);

        if($responseData['resultInfo']['code'] == 'SUCCESS' || $responseData['resultInfo']['code'] == 'REQUEST_ACCEPTED'){
            $this->data_status_code = $responseData['data']['status'];
           if($responseData['data']['status'] == 'ACCEPTED'){//受け付け済み
            return true;
           }
           if($responseData['data']['status'] == 'SUCCESS'){//成功
            return true;
           }
           if($responseData['data']['status'] == 'FAILURE'){//失敗
            return false;
           }
        }else{
            // エラー時の処理
            $this->error_code = $responseData['resultInfo']['code'];
            $this->error_code_id = $responseData['resultInfo']['codeId'];
            $this->errorMessage = $responseData['resultInfo']['message'];
            //\Log::error("PayPay Error: Code: {$this->error_code}, Message: {$this->errorMessage}");
            return false;
        }
    }

    //PayPayのキャッシュバックをキャンセルする
    public function reverseCashback($exchange_request_id,$user_id,$amount){
        
        $this->response_reset();//変数初期化

        $merchantCashbackId = $this->getCashbackId($exchange_request_id);
        if(!$merchantCashbackId){
            return false;
        }

        $requesturl = '/v2/cashback_reversal';
        $nonce = substr(bin2hex(random_bytes(16)),0,8);
        $requestBody = [
            'merchantCashbackReversalId'=> $merchantCashbackId,
            'merchantCashbackId'=> $merchantCashbackId,
            'amount'=>  ['amount'=> $amount, 'currency'=> 'JPY'],
            'requestedAt'=> date_timestamp_get(date_create()),
        ];
        $requestBody = json_encode($requestBody);
        $headers = $this->generatePayPayAuthorizationHeader($requestBody,$requesturl,$nonce);

        $request = [
            'headers' => $headers,
            'http_errors' => false,
            'timeout' => 40,
            'body' => $requestBody,
        ];

        try {
            $response = $this->client->post($requesturl, $request);
            $responseData = json_decode($response->getBody()->getContents(), true);

        } catch (\Exception $e) {
            echo 'Error: ' . $e->getMessage();
            return false;
        }

        $this->status_code = $responseData['resultInfo']['code'];

        //ログを残す
        $this->paypay_log($user_id,0,$exchange_request_id,$requesturl,$request,$responseData);

        if($responseData['resultInfo']['code'] == 'SUCCESS' || $responseData['resultInfo']['code'] == 'REQUEST_ACCEPTED'){
            return true;
        }else{
            $this->error_code = $responseData['resultInfo']['code'];
            $this->error_code_id = $responseData['resultInfo']['codeId'];
            $this->errorMessage = $responseData['resultInfo']['message'];
            
            return false;
        }
    }

    //PayPayのキャッシュバックをキャンセルの確認
    public function checkReverseCashback($exchange_request_id,$user_id){
       
        $this->response_reset();//変数初期化

        $merchantCashbackReversalId = $this->getCashbackId($exchange_request_id);
        if(!$merchantCashbackReversalId){
            return false;
        }
       
        $requesturl = '/v2/cashback_reversal/'.$merchantCashbackReversalId.'/'.$merchantCashbackReversalId;
        $nonce = substr(bin2hex(random_bytes(16)),0,8);
        $requestBody = '';
        //$requestBody = json_encode($requestBody);
        $headers = $this->generatePayPayAuthorizationHeader($requestBody,$requesturl,$nonce,'GET');

        $request = [
            'headers' => $headers,
            'http_errors' => false,
            'timeout' => 10,
        ];

        try {
            $response = $this->client->get($requesturl, $request);
            $responseData = json_decode($response->getBody()->getContents(), true);
            
        } catch (\Exception $e) {
            echo 'Error: ' . $e->getMessage();
            return false;
        }
        
        $this->status_code = $responseData['resultInfo']['code'];

        //ログを残す
        $this->paypay_log($user_id,0,$exchange_request_id,$requesturl,$request,$responseData);

        if($responseData['resultInfo']['code'] == 'ACCEPTED' || $responseData['resultInfo']['code'] == 'SUCCESS'){
            $this->data_status_code = $responseData['data']['status'];
            return true;
        }else{
            $this->error_code = $responseData['resultInfo']['code'];
            $this->error_code_id = $responseData['resultInfo']['codeId'];
            $this->errorMessage = $responseData['resultInfo']['message'];

            return false;
        }
    }

    public function response_reset(){
        $this->body = null;
        $this->status_code = null;
        $this->data_status_code = null;
        $this->merchantCashbackId = null;
        $this->error_code = null;
        $this->error_code_id = null;
        $this->errorMessage = null;
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
     * エラーコードID取得.
     * @return string エラーコードID
     */
    public function getErrorCodeId()
    {
        return $this->error_code_id;
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
     * merchantCashbackId取得.
     */
    public function getMerchantCashbackId()
    {
        return $this->merchantCashbackId;
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

    public function getResponseCode(){
        $status_code = $this->getStatusCode();
        $error_code = $this->getErrorCode();
        $error_message = $this->getErrorMessage();
        $data_status_code = $this->getDataStatusCode();
        $response_code = $error_code ? $error_code . ' : ' . $error_message : $status_code . ':' . $data_status_code;
        return $response_code;
    }

    public function getConnectException()
    {
        return $this->connect_exception;
    }

    public function paypay_log($user_id,$paypayUserId,$exchange_request_id,$requesturl,$request,$response){
        if($user_id==null){
            $account = ExchangeAccounts::where('number', $paypayUserId)->from('exchange_accounts')->where('type', ExchangeRequest::PAYPAY_TYPE)->where('deleted_at', null)->first();
            $user_id = $account->user_id;
        }
        PaypayLogs::create([
            'user_id'=>$user_id,
            'exchange_request_id'=>$exchange_request_id,
            'api_name' => $requesturl,
            'status_code' => $response['resultInfo']['code']??'',
            'request'=>json_encode($request),
            'response'=>json_encode($response),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    //システムメール送信
    public function sendSystemMail($to,$template,$message=[]){
        try {
            $mailable = new \App\Mail\PaypayAlert($to, $template, $message);
            \Mail::send($mailable);
        } catch (\Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }
    }

    //500系エラーメール送信
    public function sendErrorMail($user_id,$exchange_request_id){
        $message = [
            'message_text' => 'PayPay:give_cashback 500エラーが発生しました。下記の顧客のポイント交換データを保留にしました。',
            'user_id' => $user_id,
            'exchange_request_id' => $exchange_request_id
        ];
        $this->sendSystemMail(config('paypay.system_mail'), 'error',$message);

    }

    //保留中データ用ポイント付与通知メール送信
    public function sendCheckRetryCashBackMail($user_id,$exchange_request_id,$type){
        $message = [
            'user_id' => $user_id,
            'exchange_request_id' => $exchange_request_id
        ];
        if($type=='SUCCESS'){
            $message['message_text'] = 'PayPay:give_cashback 下記のユーザのポイント付与が完了しました。';
        }elseif($type=='ACCEPTED'){
            $message['message_text'] = 'PayPay:give_cashback 下記のユーザのポイント付与が受付済みになりました。';
        }
        $this->sendSystemMail(config('paypay.system_mail'), 'error',$message);

    }

    public function timeout(){
        $client = new Client([
            'base_uri' => 'http://host.docker.internal:8090/'
        ]);
        $content_type = 'application/json';
        $headers = [
            'content-type' => $content_type,
        ];

        $request = [
            'headers' => $headers,
            'http_errors' => false,
            'timeout' => 3,
        ];
        $response = $client->get('test', $request);
            $responseData = json_decode($response->getBody()->getContents(), true);
            
            return $responseData;
        // try {
        //     $response = $client->get('test', $request);
        //     $responseData = json_decode($response->getBody()->getContents(), true);
        //     $body = json_decode($response->getBody(), true);
        //     dd($responseData);
        // }catch(ConnectException $e) {
        //    // dd($e->getCode());
        //    dd($e->getHandlerContext());
        //     //\Log::error('Error: ' . $e->getMessage());
        //     //return false;
        // } catch (\Exception $e) {
        //     dd($e->getMessage());
        //     //\Log::error('Error: ' . $e->getMessage());
        //     //return false;
        // }


    }
}

