<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\VariantOptionRequest;
use App\Http\Resources\Admin\VariantOptionResource;
use App\Response\ApiResponse;
use App\Services\VariantOptionService;

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
}
