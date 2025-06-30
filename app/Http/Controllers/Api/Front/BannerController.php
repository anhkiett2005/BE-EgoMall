<?php
namespace App\Http\Controllers\Api\Front;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Response\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BannerController extends Controller
{
    // GET /api/banners
    public function index()
    {
        try {
            $banners = Banner::where('status', '!=', 0)
                            ->get();

            // Xử lý dữ liệu trả về
            $listBanner = collect();

            $banners->each(function ($banner) use ($listBanner) {
                $listBanner->push([
                    'title' => $banner->title,
                    'image_url' => $banner->image_url,
                    'link_url' => $banner->link_url,
                    'position' => $banner->position,
                ]);
            });

            return ApiResponse::success('Lấy danh sách banner thành công!!', data: $listBanner);
        } catch (\Exception $e) {
           logger('Log bug',[
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra!!');
        }
    }

    // // POST /api/banners
    // public function store(Request $request)
    // {
    //     try {
    //     $data = $request->validate([
    //         'title' => 'required|string|max:255',
    //         'image_url' => 'required|string',
    //         'link_url' => 'required|string',
    //         'position' => 'required|string',
    //         'start_date' => 'required|date',
    //         'end_date' => 'required|date|after_or_equal:start_date',
    //         'status' => 'boolean',
    //     ]);
    //     $banner = Banner::create($data);
    //     return response()->json(['message' => 'success', 'data' => $banner ], 201);
    //     } catch (\Illuminate\Validation\ValidationException $e) {
    //         Log::error(__CLASS__ . ' @ ' . $e->getMessage() . ' Line: ' . $e->getLine());
    //         return response()->json(['message' => 'Validation error: ' . $e->getMessage()], 422);
    //     } catch (\Exception $e) {
    //         Log::error(__CLASS__ . ' @ ' . $e->getMessage() . ' Line: ' . $e->getLine());
    //         return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
    //     }

    // }

    // // GET /api/banners/{id}
    // public function show($id)
    // {
    //     try {
    //     $banner = Banner::findOrFail($id);
    //     return response()->json(['message' => 'success', 'data' => $banner ]);
    //     } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
    //         Log::error(__CLASS__ . ' @ ' . $e->getMessage() . ' Line: ' . $e->getLine());
    //         return response()->json(['message' => 'Banner not found'], 404);
    //     } catch (\Exception $e) {
    //         Log::error(__CLASS__ . ' @ ' . $e->getMessage() . ' Line: ' . $e->getLine());
    //         return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
    //     }
    // }

    // // PUT/PATCH /api/banners/{id}
    // public function update(Request $request, $id)
    // {
    //     try {
    //         $banner = Banner::findOrFail($id);
    //         $data = $request->validate([
    //         'title' => 'string|max:255',
    //         'image_url' => 'string',
    //         'link_url' => 'string',
    //         'position' => 'string',
    //         'start_date' => 'date',
    //         'end_date' => 'date|after_or_equal:start_date',
    //         'status' => 'boolean',

    //         ]);
    //         $banner->update($data);
    //         return response()->json(['message' => 'success', 'data' => $banner ]);
    //     } catch (\Illuminate\Validation\ValidationException $e) {
    //         Log::error(__CLASS__ . ' @ ' . $e->getMessage() . ' Line: ' . $e->getLine());
    //         return response()->json(['message' => 'Validation error: ' . $e->getMessage()], 422);
    //     } catch (\Exception $e) {
    //         Log::error(__CLASS__ . ' @ ' . $e->getMessage() . ' Line: ' . $e->getLine());
    //         return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
    //     }
    // }

    // // DELETE /api/banners/{id}
    // public function destroy($id)
    // {
    //     try {
    //     Banner::destroy($id);
    //     return response()->json(['message' => 'Deleted'], 200);
    //     } catch (\Exception $e) {
    //         Log::error(__CLASS__ . ' @ ' . $e->getMessage() . ' Line: ' . $e->getLine());
    //         return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
    //     }
    // }
}
