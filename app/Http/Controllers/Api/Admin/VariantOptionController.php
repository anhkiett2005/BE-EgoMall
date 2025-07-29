<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVariantValueRequest;
use App\Http\Requests\UpdateVariantValueRequest;
use App\Http\Requests\VariantOptionRequest;
use App\Http\Resources\Admin\VariantOptionResource;
use App\Response\ApiResponse;
use App\Services\VariantOptionService;
use Symfony\Component\HttpFoundation\Response;

class VariantOptionController extends Controller
{
    protected $service;

    public function __construct(VariantOptionService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $options = $this->service->index();
        return ApiResponse::success('Lấy danh sách thành công!', 200, VariantOptionResource::collection($options)->toArray(request()));
    }

    public function show($id)
    {
        $option = $this->service->show($id);
        return ApiResponse::success('Chi tiết tùy chọn', 200, (new VariantOptionResource($option))->toArray(request()));
    }

    public function store(VariantOptionRequest $request)
    {
        $option = $this->service->store($request->validated());
        return ApiResponse::success('Tạo mới thành công!', 201, (new VariantOptionResource($option))->toArray(request()));
    }

    public function update(VariantOptionRequest $request, $id)
    {
        $option = $this->service->update($id, $request->validated());
        return ApiResponse::success('Cập nhật thành công!', 200, (new VariantOptionResource($option))->toArray(request()));
    }

    public function destroy($id)
    {
        $this->service->destroy($id);
        return ApiResponse::success('Xóa thành công!');
    }

    public function createValues(StoreVariantValueRequest $request, $optionId)
    {
        try {
            $valueOption = $this->service->createValues($request, $optionId);

            // định dạng dữ liệu trả về FE
            $data = [
                'id' => $valueOption->id,
                'name' => $valueOption->value,
                'option_id' => $valueOption->option_id
            ];

            if ($valueOption) {
                return ApiResponse::success('Tạo giá trị thành công!!', Response::HTTP_CREATED, data: $data);
            }
        } catch (ApiException $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode(), $e->getErrors());
        }
    }

    public function updateValues(UpdateVariantValueRequest $request, $id)
    {
        try {
            $value = $this->service->updateValues($id, $request->validated());

            $data = [
                'id' => $value->id,
                'value' => $value->value,
                'option_id' => $value->option_id
            ];

            return ApiResponse::success('Cập nhật giá trị thành công!', 200, data: $data);
        } catch (ApiException $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode(), $e->getErrors());
        }
    }

    public function destroyValues($id)
    {
        try {
            $this->service->destroyValues($id);
            return ApiResponse::success('Xóa giá trị thành công!');
        } catch (ApiException $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode(), $e->getErrors());
        }
    }
}
