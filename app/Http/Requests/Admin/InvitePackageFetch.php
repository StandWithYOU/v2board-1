<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class InvitePackageFetch extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'filter.*.key' => 'required|in:status,id,user_id,from_user_id',
            'filter.*.condition' => 'required|in:>,<,=,>=,<=,模糊,!=,like,in',
            'filter.*.value' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'filter.*.key.required' => '过滤键不能为空',
            'filter.*.key.in' => '过滤键参数有误',
            'filter.*.condition.required' => '过滤条件不能为空',
            'filter.*.condition.in' => '过滤条件参数有误',
            'filter.*.value.required' => '过滤值不能为空'
        ];
    }
}
