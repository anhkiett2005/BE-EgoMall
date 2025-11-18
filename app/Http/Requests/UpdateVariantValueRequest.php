<?php

namespace App\Http\Requests;

use App\Traits\FormRequestResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class UpdateVariantValueRequest extends FormRequest
{

    use FormRequestResponseTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'value' => 'required|string|max:255'
        ];
    }

    public function messages(): array
    {
        return [
            'value.required' => 'Giá trị là bắt buộc!',
            'value.string'   => 'Giá trị phải là chuỗi!',
            'value.max'      => 'Giá trị không được vượt quá 255 ký tự!',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $this->validationErrorResponse($validator->errors()->toArray());
    }
}
