<?php
namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Brand;
use Exception;

class BrandServices {


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
            logger('Log bug modify product',[
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra!!', 500);
        }
     }
}
