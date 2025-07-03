<?php

namespace App\Services;

use App\Classes\Common;
use App\Exceptions\ApiException;
use App\Http\Resources\Admin\BrandResource as AdminBrandResource;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class BrandServices
{


    /**
     * Lấy toàn bộ danh sách brands
     */

    public function modifyIndex()
    {
        try {
            $brands = Brand::active()
                ->get();

            $listBrands = collect();

            $brands->each(function ($brand) use ($listBrands) {
                $listBrands->push([
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'slug' => $brand->slug,
                    'logo' => $brand->logo,
                    'description' => $brand->description,
                    'is_active' => $brand->is_active,
                    'is_featured' => $brand->is_featured,
                    'created_at' => $brand->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $brand->updated_at->format('Y-m-d H:i:s'),
                ]);
            });

            return $listBrands;
        } catch (Exception $e) {
            logger('Log bug modify product', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra!!', 500);
        }
    }


    public function modifyShow(string $id)
    {
        try {
            $brand = Brand::findOrFail($id);
            return $brand;
        } catch (Exception $e) {
            throw new ApiException('Không tìm thấy thương hiệu!', 404);
        }
    }

    public function modifyStore(array $data)
    {
        try {
            if (request()->hasFile('logo')) {
                $data['logo'] = Common::uploadImageToCloudinary(request()->file('logo'), 'egomall/brands');
            }

            $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

            return Brand::create($data);
        } catch (Exception $e) {
            throw new ApiException('Tạo thương hiệu thất bại!', 500, [$e->getMessage()]);
        }
    }

    public function modifyUpdate(array $data, string $id)
    {
        try {
            $brand = Brand::findOrFail($id);

            if (request()->hasFile('logo')) {
                $data['logo'] = Common::uploadImageToCloudinary(request()->file('logo'), 'egomall/brands');
            }

            $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

            $brand->update($data);
            return $brand;
        } catch (Exception $e) {
            throw new ApiException('Cập nhật thương hiệu thất bại!', 500, [$e->getMessage()]);
        }
    }

    public function modifyDestroy(string $id)
    {
        try {
            $brand = Brand::findOrFail($id);

            if ($brand->products()->exists()) {
                throw new ApiException('Không thể xóa thương hiệu có sản phẩm liên quan!', 422);
            }

            $brand->delete();
        } catch (Exception $e) {
            throw new ApiException('Xóa thương hiệu thất bại!', 500, [$e->getMessage()]);
        }
    }

    public function modifyTrashed()
    {
        try {
            $trashed = Brand::onlyTrashed()->latest()->get();
            return AdminBrandResource::collection($trashed);
        } catch (Exception $e) {
            throw new ApiException('Lấy danh sách thương hiệu đã xoá thất bại!', 500);
        }
    }

    public function modifyRestore(string $id)
    {
        try {
            $brand = Brand::onlyTrashed()->findOrFail($id);
            $brand->restore();
            return new AdminBrandResource($brand);
        } catch (Exception $e) {
            throw new ApiException('Khôi phục thương hiệu thất bại!', 500);
        }
    }
}