<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\VariantOption;
use App\Models\VariantValue;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class VariantOptionService
{
    public function index()
    {
        return VariantOption::with('variantValues')->latest()->get();
    }

    public function show(int $id): VariantOption
    {
        $variantOption = VariantOption::with('variantValues')->find($id);

        if (!$variantOption) {
            throw new ApiException('Không tìm thấy tùy chọn biến thể!', Response::HTTP_NOT_FOUND);
        }

        return $variantOption;
    }

    public function store(array $data): VariantOption
    {
        return DB::transaction(function () use ($data) {
            $option = VariantOption::create([
                'name' => $data['name']
            ]);

            if (!empty($data['values']) && is_array($data['values'])) {
                $insertValues = array_map(fn($value) => ['value' => $value], $data['values']);
                $option->variantValues()->createMany($insertValues);
            }

            return $option->load('variantValues');
        });
    }

    public function update(int $id, array $data): VariantOption
    {
        $variantOption = VariantOption::find($id);

        if (!$variantOption) {
            throw new ApiException('Không tìm thấy tùy chọn để cập nhật!', Response::HTTP_NOT_FOUND);
        }

        $variantOption->update($data);

        return $variantOption;
    }

    public function destroy(int $id): bool
    {
        $variantOption = VariantOption::with(['variantValues', 'categoryOptions'])->find($id);

        if (!$variantOption) {
            throw new ApiException('Không tìm thấy tùy chọn để xóa!', Response::HTTP_NOT_FOUND);
        }

        if ($variantOption->variantValues->count() > 0 || $variantOption->categoryOptions->count() > 0) {
            throw new ApiException('Không thể xóa vì tùy chọn đã được sử dụng!', Response::HTTP_BAD_REQUEST);
        }

        return $variantOption->delete();
    }





    /**
     * Tạo mới 1 value cho option của variant
     */
    public function createValues($request, $optionId)
    {
        DB::beginTransaction();

        try {
            $data = $request->all();

            // tìm option
            $option = VariantOption::find($optionId);

            // Nếu kh tồn tại throw Exception luôn
            if (!$option) {
                throw new ApiException('Không tìm thấy tùy chọn!!', Response::HTTP_NOT_FOUND);
            }

            // check trùng data name gửi lên
            if ($option->variantValues->contains('value', $data['value'])) {
                throw new ApiException('Giá trị này đã được sử dụng', Response::HTTP_BAD_REQUEST);
            }

            // Tạo value
            $value = $option->variantValues()->create([
                'value' => $data['value']
            ]);

            DB::commit();
            return $value;
        } catch (ApiException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            logger('Log bug create variant value for option', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra!!', 500);
        }
    }

      /**
     * Cập nhật 1 giá trị của variant
     */
    public function updateValues(int $id, array $data): VariantValue
    {
        $value = VariantValue::find($id);

        if (!$value) {
            throw new ApiException('Không tìm thấy giá trị để cập nhật!', Response::HTTP_NOT_FOUND);
        }

        // Check nếu value trùng (theo option_id), tránh cập nhật thành giá trị đã tồn tại
        $duplicate = VariantValue::where('option_id', $value->option_id)
            ->where('value', $data['value'])
            ->where('id', '!=', $id)
            ->exists();

        if ($duplicate) {
            throw new ApiException('Giá trị này đã tồn tại trong tùy chọn!', Response::HTTP_BAD_REQUEST);
        }

        $value->update($data);

        return $value;
    }

    /**
     * Xóa mềm 1 giá trị của variant
     */
    public function destroyValues(int $id): bool
    {
        $value = VariantValue::find($id);

        if (!$value) {
            throw new ApiException('Không tìm thấy giá trị để xóa!', Response::HTTP_NOT_FOUND);
        }

        return $value->delete();
    }
}
