<?php

namespace App\Http\Controllers\Api\Front;

use App\Classes\Common;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\UploadImageRequest;
use App\Response\ApiResponse;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function upload(UploadImageRequest $request)
    {
        try {
            $file = $request->file('file');
            $folder = $request->folder;
            $imageUrlUpload = '';
            if(isset($file) && isset($folder)) {
                // upload image to cloudinary
                $imageUrlUpload = Common::uploadImageToCloudinary($file, $folder);
            }

            $data = [
                'url' => $imageUrlUpload
            ];

            return ApiResponse::success('Upload hình ảnh thành công!!', data: $data);
        }catch (\Exception $e) {
            logger('Log bug', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra!!!');
        }
    }
}
