<?php
namespace App\External;

use GuzzleHttp\Client;

/**
 * Colleee成果.
 * @author t_moriizumi
 */
class ColleeeKick {
    private $path = null;
    private $params = null;
    private $file_params = null;
    private $body = null;

    /**
     * 設定値取得.
     * @param string $key キー
     * @return mixed 設定値
     */
    private static function getConfig(string $key) {
        // 読み込む設定を環境によって切り替える
        return config('colleee_kick.'.$key);
    }

    /**
     * 成果CSVインポートオブジェクト取得.
     * @param int $admin_id 管理者ID
     * @param int $status 状態
     * @param string $file_path CSVファイルパス
     * @return ColleeeKick Colleee成果オブジェクト
     */
    public static function getImportProgram(int $admin_id, int $status, string $file_path) : ColleeeKick {
        $colleee_kick = new self();
        $colleee_kick->path = '/csv/import_program';
        $colleee_kick->params = ['admin_id' => $admin_id, 'status' => $status];
        $colleee_kick->file_params = ['file' => fopen($file_path, 'r')];

        return $colleee_kick;
    }

    /**
     * 成果CSVインポートオブジェクト取得.
     * @param int $admin_id 管理者ID
     * @param string $file_path CSVファイルパス
     * @return ColleeeKick Colleee成果オブジェクト
     */
    public static function getImportProgramless(int $admin_id, string $file_path) : ColleeeKick {
        $colleee_kick = new self();
        $colleee_kick->path = '/csv/import_programless';
        $colleee_kick->params = ['admin_id' => $admin_id];
        $colleee_kick->file_params = ['file' => fopen($file_path, 'r')];

        return $colleee_kick;
    }

    /**
     * 実行.
     * @return bool 成功の場合はtrueを、失敗の場合はfalseを返す
     */
    public function execute() : bool {
        $client = new Client();

        // URL取得
        $url = self::getConfig('URL');

        $options = [
            'http_errors' => false,
            'timeout' => 60];

        // メソッドとクエリ作成
        if (isset($this->file_params)) {
            $method = 'POST';
            $multipart_list = [];
            if (!empty($this->params)) {
                foreach ($this->params as $key => $value) {
                    $multipart_list[] = ['name' => $key, 'contents' => $value];
                }
            }
            foreach ($this->file_params as $key => $value) {
                $multipart_list[] = ['name' => $key, 'contents' => $value];
            }
            $options['multipart'] = $multipart_list;
        } elseif (isset($this->params)) {
            $method = 'POST';
            $options['form_params'] = $this->params;
        } else {
            $method = 'GET';
        }

        // プロキシ
        if (!self::getConfig('PROXY')) {
            $options['proxy'] = '';
        }
        // SSL証明書回避
        if (!self::getConfig('SSL_VERIFY')) {
            $options['verify'] = false;
        }

        try {
            // リクエスト実行
            $response = $client->request($method, $url.$this->path, $options);

            // HTTPステータス確認
            $status = $response->getStatusCode();
            if ($status != 200) {
                \Log::info('ColleeeKick[staus:'.$status.']');
                return false;
            }
            $this->body = $response->getBody();
        } catch (\Exception $e) {
            \Log::info('ColleeeKick:'.$e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * 結果取得.
     * @return string 結果
     */
    public function getBody() {
        return $this->body;
    }
}
