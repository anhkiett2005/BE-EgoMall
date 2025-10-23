<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email',
            'phone'                 => [
                'required',
                'regex:/^(0|\+84)(3[2-9]|5[2689]|7[06-9]|8[1-9]|9[0-9])[0-9]{7}$/',
                'string'
            ],
            'password'              => [
                'required',
                'string',
                'min:8',
                // ít nhất 1 chữ hoa, 1 chữ thường, 1 số, 1 ký tự đặc biệt
                'regex:/^[A-Z](?=.*[a-z])(?=.*\d)(?=.*[\W_]).{7,}$/',
                'confirmed'
            ],
            'password_confirmation' => 'required|string|min:8',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Vui lòng nhập tên.',
            'name.string' => 'Tên phải là chuỗi.',
            'name.max' => 'Tên tối đa là 255 ký tự.',

            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            // 'email.unique' => 'Email đã tồn tại trong hệ thống.',

            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại không hợp lệ. Vui lòng nhập đúng định dạng Việt Nam (VD: 090xxxxxxx hoặc +8490xxxxxxx).',
            'phone.string' => 'Số điện thoại phải là chuỗi.',
            // 'phone.unique' => 'Số điện thoại đã tồn tại trong hệ thống.',

            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.string' => 'Mật khẩu phải là chuỗi.',
            'password.min' => 'Mật khẩu tối đa là 8 ký tự.',
            'password.regex' => 'Mật khẩu phải gồm ít nhất 1 chữ hoa, 1 chữ thường, 1 số và 1 ký tự đặc biệt.',
            'password.confirmed' => 'Mật khẩu không khớp nhau.',

            'password_confirmation.required' => 'Vui lòng nhập xác nhận mật khẩu.',
            'password_confirmation.string' => 'Xâc nhân mật khẩu phải là chuỗi.',
            'password_confirmation.min' => 'Xâc nhân mật khẩu tối đa là 8 ký tự.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $email = $this->input('email');
            $phone = $this->input('phone');

            if (User::where('email', $email)->exists()) {
                $validator->errors()->add('email', 'Email đã tồn tại trong hệ thống.');
            }

            if (User::where('phone', $phone)->exists()) {
                $validator->errors()->add('phone', 'Số điện thoại đã tồn tại trong hệ thống.');
            }

            // Nếu có lỗi email hoặc phone thì bỏ qua check mật khẩu
            if ($validator->errors()->has('email') || $validator->errors()->has('phone')) {
                $validator->errors()->forget('password');
                $validator->errors()->forget('password_confirmation');
            }
        });
    }


    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Validation errors',
            'code' => 422,
            'errors' => $validator->errors()
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}
