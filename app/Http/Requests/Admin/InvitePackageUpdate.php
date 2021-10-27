<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class InvitePackageUpdate extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'status' => 'in:0,1,-1',
        ];
    }

    public function messages()
    {
        return [
            'status.in' => '订单状态格式不正确',
        ];
    }
}
