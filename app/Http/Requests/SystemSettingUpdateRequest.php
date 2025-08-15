<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SystemSettingUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            '*.nullable' => 'nullable' // placeholder, FE có thể gửi nhiều key bất kỳ
        ];
    }

    public function messages(): array
    {
        return [
            '*.nullable' => 'Trường này có thể để trống',
        ];
    }
}
