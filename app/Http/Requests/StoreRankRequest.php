<?php

namespace App\Http\Requests;

use App\Models\Rank;
use App\Models\SystemSetting;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class StoreRankRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation()
    {
        // 🧩 Lấy cấu hình rank mode trong hệ thống
        $rankMode = SystemSetting::where('setting_key', 'rank_mode')
            ->where('setting_group', 'rank_setting')
            ->value('setting_value');

        if (!$rankMode) {
            throw new HttpResponseException(response()->json([
                'message' => 'Validation errors',
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => [
                    'rank_mode' => ['Chưa cấu hình chế độ xét rank trong hệ thống.']
                ]
            ], Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        // 🧠 Kiểm tra rank mặc định trước khi chạy rule
        if ($rankMode === 'amount') {
            $defaultRankExists = Rank::where('min_spent_amount', 0)->exists();
            if (!$defaultRankExists) {
                throw new HttpResponseException(response()->json([
                    'message' => 'Validation errors',
                    'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors' => [
                        'default_rank' => [
                            'Hệ thống chưa có rank mặc định theo chi tiêu. Vui lòng thiết lập rank mặc định trước.'
                        ]
                    ]
                ], Response::HTTP_UNPROCESSABLE_ENTITY));
            }else {
                throw new HttpResponseException(response()->json([
                    'message' => 'Validation errors',
                    'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors' => [
                        'default_rank' => [
                            'Hệ thống đã có rank mặc định theo chi tiêu. Không thể tạo rank mặc định theo chi tiêu.'
                        ]
                    ]
                ],Response::HTTP_UNPROCESSABLE_ENTITY));
            }
        } elseif ($rankMode === 'point') {
            $defaultRankExists = Rank::whereNull('minimum_point')->exists();
            if (!$defaultRankExists) {
                throw new HttpResponseException(response()->json([
                    'message' => 'Validation errors',
                    'code' => 422,
                    'errors' => [
                        'default_rank' => [
                            'Hệ thống chưa có rank mặc định theo điểm. Vui lòng thiết lập rank mặc định trước.'
                        ]
                    ]
                ], Response::HTTP_UNPROCESSABLE_ENTITY));
            }else {
                throw new HttpResponseException(response()->json([
                    'message' => 'Validation errors',
                    'code' => 422,
                    'errors' => [
                        'default_rank' => [
                            'Hệ thống đã có rank mặc định theo điểm. Không thể tạo rank mặc định theo điểm.'
                        ]
                    ]
                ],Response::HTTP_UNPROCESSABLE_ENTITY));
            }
        }
    }


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rankDetails' => 'required|array',
            'rankDetails.*.name' => 'required|string|max:255|unique:ranks,name',
            'rankDetails.*.image' => ['nullable','url','regex:/\.(jpg|jpeg|png|gif|webp)$/i'],
            'rankDetails.*.amount_to_point' => 'required|numeric',
            'rankDetails.*.min_spent_amount' => 'nullable|numeric|required_without:rankDetails.*.minimum_point|prohibits:rankDetails.*.minimum_point',
            'rankDetails.*.converted_amount' => 'required|numeric',
            'rankDetails.*.discount' => 'nullable|numeric',
            'rankDetails.*.maximum_discount_order' => 'required_if:rankDetails.*.checked,==,true',
            'rankDetails.*.type_time_receive' => 'nullable|string|max:255',
            'rankDetails.*.time_receive_point' => 'nullable|string|max:255',
            'rankDetails.*.minimum_point' => 'nullable|numeric|required_without:rankDetails.*.min_spent_amount|prohibits:rankDetails.*.min_spent_amount',
            'rankDetails.*.maintenance_point' => 'nullable|numeric',
            'rankDetails.*.point_limit_transaction' => 'nullable|numeric',
            'rankDetails.*.status_payment_point' => 'nullable|boolean|in:0,1',
        ];
    }

    public function messages()
    {
        return [
            'rankDetails.required' => 'Vui lòng gửi lên danh sách rank.',
            'rankDetails.array' => 'Danh sách rank phải là mảng.',

            'rankDetails.*.name.required' => 'Tên rank là bắt buộc.',
            'rankDetails.*.name.string' => 'Tên rank phải là chuỗi.',
            'rankDetails.*.name.max' => 'Tên rank không quá 255 ký tự.',
            'rankDetails.*.name.unique' => 'Tên rank bị trùng.',

            'rankDetails.*.image.url' => 'Hình ảnh phải là url hợp lệ.',
            'rankDetails.*.image.regex' => 'Hình ảnh phải là jpeg, png, jpg, gif, hoặc webp.',

            'rankDetails.*.amount_to_point.required' => 'Vui lòng thiết lập số tiền đổi điểm.',
            'rankDetails.*.amount_to_point.numeric' => 'Số tiền đổi điểm phải là số.',
            // 'rankDetails.*.amount_to_point.required_without' => 'Vui nhập số tiền đổi điểm nếu không thiết lập điểm tích lũy.',
            // 'rankDetails.*.amount_to_point.prohibits' => 'Không thể nhập số tiền đổi điểm khi được thiết lập điểm tích lũy.',

            'rankDetails.*.min_spent_amount.numeric' => 'Điều kiện tổng chi tiêu rank phải là số.',
            'rankDetails.*.min_spent_amount.required_without' => 'Vui lòng nhập tổng chi tiêu nếu không thiết lập điểm tích lũy.',
            'rankDetails.*.min_spent_amount.prohibits' => 'Không thể nhập tổng chi tiêu khi đã thiết lập điểm tích lũy.',


            'rankDetails.*.converted_amount.required' => 'Vui lòng thiết lập số điểm đổi tiền.',
            'rankDetails.*.converted_amount.numeric' => 'Số điểm đổi tiền phải là số.',

            'rankDetails.*.discount.numeric' => 'Giảm giá phải là số.',

            'rankDetails.*.maximum_discount_order.required_if' => 'Vui lòng thiết lập giảm giá lớn nhất trên đơn hàng.',

            'rankDetails.*.type_time_receive.string' => 'Chu kỳ nhận điểm phải la chuỗi.',
            'rankDetails.*.type_time_receive.max' => 'Chu kỳ nhận điểm không quá 255 ký tự.',

            'rankDetails.*.time_receive_point.string' => 'Giá trị chu kỳ nhận điểm phải la chuỗi.',
            'rankDetails.*.time_receive_point.max' => 'Giá trị chu kỳ nhận điểm không quá 255 ký tự.',

            'rankDetails.*.minium_point.numeric' => 'Điểm tích lũy phải là số.',
            'rankDetails.*.minimum_point.required_without' => 'Vui lòng nhập điểm tích lũy nếu không thiết lập tổng chi tiêu.',
            'rankDetails.*.minimum_point.prohibits' => 'Không thể nhập điểm tích lũy khi đã thiết lập tổng chi tiêu.',

            'rankDetails.*.maintenance_point.numeric' => 'Điểm tối thiểu duy trì rank phải là số.',

            'rankDetails.*.point_limit_transaction.numeric' => 'Số điểm giao dịch tối đa phải là số.',

            'rankDetails.*.status_payment_point.boolean' => 'Trạng thái thanh toán điểm phải là true hoặc false.',

        ];
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
