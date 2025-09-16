<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\SystemSettingStoreRequest;
use App\Http\Requests\SystemSettingUpdateRequest;
use App\Http\Resources\Admin\SystemSettingResource;
use App\Models\SystemSetting;
use App\Response\ApiResponse;
use App\Services\SystemSettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;

class SystemSettingController extends Controller
{
    protected SystemSettingService $service;

    public function __construct(SystemSettingService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $group = $request->query('group');
        if ($group && !in_array($group, SystemSetting::GROUPS, true)) {
            return ApiResponse::error('Nhóm cấu hình không hợp lệ!', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $models = $this->service->list($group);

        return ApiResponse::success(
            'Lấy danh sách cấu hình thành công!',
            200,
            SystemSettingResource::collection($models)->toArray($request)
        );
    }

    public function store(SystemSettingStoreRequest $request)
    {
        try {
            $stored = $this->service->create($request);

            if($stored) {
                return ApiResponse::success('Thêm cấu hình hệ thống thành công!', Response::HTTP_CREATED);
            }
        }catch (ApiException $e) {
            throw $e;
        }catch (\Exception $e) {
            logger('Log bug SystemSettingController@store', [
                'error_message' => $e->getMessage(),
                'error_file'   => $e->getFile(),
                'error_line'   => $e->getLine(),
                'stack_trace'  => $e->getTraceAsString(),
            ]);

            throw new ApiException(
                'Không thể thêm cấu hình hệ thống!',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function update(SystemSettingUpdateRequest $request)
    {
        try {
            // cho phép set null? -> đổi tham số thứ 2 nếu cần
            $changed = $this->service->update($request->all(), false);

            return ApiResponse::success(
                'Cập nhật cấu hình thành công!',
                Response::HTTP_OK,
                ['changed_keys' => $changed]
            );
        } catch (\App\Exceptions\ApiException $e) {
            throw $e; // đã chuẩn hoá trong ApiException
        } catch (\Throwable $e) {
            logger('Log bug SystemSettingController@update', [
                'keys'         => array_keys($request->all()),
                'error_message' => $e->getMessage(),
                'error_file'   => $e->getFile(),
                'error_line'   => $e->getLine(),
                'stack_trace'  => $e->getTraceAsString(),
            ]);

            throw new ApiException(
                'Không thể cập nhật cấu hình hệ thống!',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function sendTestEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        try {
            $mailConfig = $this->service->getEmailConfig(true);
            $this->service->applyMailConfig($mailConfig);

            Mail::raw('Đây là email test từ hệ thống EgoMall', function ($m) use ($request) {
                $m->to($request->email)->subject('Test Email từ EgoMall');
            });

            // message, code, data
            return ApiResponse::success(
                'Đã gửi email test tới ' . $request->email,
                Response::HTTP_OK,
                []
            );
        } catch (\Throwable $e) {
            logger('Log bug sendTestEmail', [
                'error_message' => $e->getMessage(),
                'error_file'    => $e->getFile(),
                'error_line'    => $e->getLine(),
                'stack_trace'   => $e->getTraceAsString(),
            ]);
            throw new ApiException('Gửi email test thất bại: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
