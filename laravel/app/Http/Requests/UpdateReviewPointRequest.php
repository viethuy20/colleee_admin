<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\AfterExistingStartDate ;

class UpdateReviewPointRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;  // You can add custom authorization logic here if necessary
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'sta' => [
                'required',
                'date',
                new AfterExistingStartDate($this->id)
            ],
            'point' => 'required|integer|min:1',
            'id' => 'required|integer',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'sta.required' => '開始日は必須です。',
            'sta.date' => '開始日は有効な日付である必要があります。',
            'point.required' => '口コミ配布ポイントは必須です。',
            'point.integer' => '口コミ配布ポイントは整数で入力してください。',
            'id.required' => 'IDは必須です。',
            'id.integer' => 'IDは整数である必要があります。',
        ];
    }

}
