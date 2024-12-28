<?php
namespace App\Paginators;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use WrapPhp;

class BasePaginator extends LengthAwarePaginator
{
    public static $defaultView = 'elements.base_paginator';

    /**
     * ページネーター取得.
     * @params array $default_params デフォルトパラメーター
     * @params function $builder_function クエリビルダー
     * @params int $limit 表示件数
     */
    public static function getDefault(array $default_params, $builder_function, int $limit = 100)
    {
        // 検索条件を取得
        $params = [];
        $a_params = [];
        foreach ($default_params as $key => $default_value) {
            $value = request()->input($key);
            $params[$key] = $value ?? $default_value;
            if (!isset($value) || $value == $default_value) {
                continue;
            }
            $a_params[$key] = $params[$key];
        }
        // クエリービルダー取得
        $builder = $builder_function($params);

        // 総件数取得
        $total = $builder->count();

        // ページ数
        $page = min(max($params['page'], 1), ceil($total / $limit));
        // リスト取得
        $list = $builder->take($limit)
            ->skip(($page - 1) * $limit)
            ->get();

        // ページネーター取得
        $paginator = new self($list, $total, $limit, $page);
        $paginator->appends($a_params);
        $paginator->setPath(request()->url());
        return $paginator;
    }

    /**
     * クエリパラメータ取得.
     * @param string|null $key キー
     * @return mixed 値
     */
    public function getQuery(?string $key = null)
    {
        if (!isset($key)) {
            return $this->query;
        }
        return $this->query[$key] ?? null;
    }

    /**
     * 拡張パラメーターによるURL取得.
     * @param array $parameters パラメーター
     * @return string URL
     */
    public function expUrl(array $parameters)
    {
        if (WrapPhp::count($this->query) > 0) {
            $parameters = array_merge($this->query, $parameters);
        }

        return $this->path()
            .(Str::contains($this->path(), '?') ? '&' : '?')
            .Arr::query($parameters)
            .$this->buildFragment();
    }
}
