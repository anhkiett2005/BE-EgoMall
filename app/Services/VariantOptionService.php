<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\VariantOption;
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
        return VariantOption::create($data);
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
}
