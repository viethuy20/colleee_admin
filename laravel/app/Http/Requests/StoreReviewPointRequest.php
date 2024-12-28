<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\AfterExistingStartDate;

class StoreReviewPointRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'rc_point' => 'required|integer|min:1',
            'sta' => ['required', 'date',new AfterExistingStartDate($this->id)],
        ];
    }

    public function messages()
    {
        return [
            'rc_point.required' => '口コミ配布ポイントは必須です。',
            'rc_point.integer'  => '口コミ配布ポイントは整数で入力してください。',
            'sta.required'      => 'スケジュールは必須です。',
        ];
    }
}
