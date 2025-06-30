<?php
namespace App\Http\Controllers\Api\Front;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Slider;
use App\Models\Sliders;
use App\Response\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SliderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $sliders = Slider::with('images')
                             ->get();


            // Xử lý dữ liệu trả về
            $listSlider = collect();

            $sliders->each(function ($slider) use ($listSlider) {
                $listSlider->push([
                    'name' => $slider->name,
                    'description' => $slider->description,
                    'position' => $slider->position,
                    'status' => $slider->status,
                ]);
            });

            return ApiResponse::success('Lấy danh sách slider thành công!!', data: $listSlider);
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

//     /**
//      * Show the form for creating a new resource.
//      */
//     public function store(Request $request)
//     {
//         try {
//             $data = $request->validate([
//                 'name' => 'required|string|max:255',
//                 'description' => 'required|string',
//                 'position' => 'required|string',
//                 'status' => 'boolean',
//             ]);
//             $slider = Sliders::create($data);
//             return response()->json(['slider' => $slider], 201);
//         } catch (\Illuminate\Validation\ValidationException $e) {
//             Log::error(__CLASS__ . ' @ ' . $e->getMessage() . ' Line: ' . $e->getLine());
//             return response()->json(['message' => 'Validation error: ' . $e->getMessage()], 422);
//         } catch (\Exception $e) {
//             Log::error(__CLASS__ . ' @ ' . $e->getMessage() . ' Line: ' . $e->getLine());
//             return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
//         }
//     }

//     /**
//      * Display the specified resource.
//      */
//     public function show($id)
//     {
//         try {
//             $slider = Sliders::findOrFail($id);
//             return response()->json(['slider' => $slider]);
//         } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
//             Log::error(__CLASS__ . ' @ ' . $e->getMessage() . ' Line: ' . $e->getLine());
//             return response()->json(['message' => 'Slider not found'], 404);
//         } catch (\Exception $e) {
//             Log::error(__CLASS__ . ' @ ' . $e->getMessage() . ' Line: ' . $e->getLine());
//             return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
//         }
//     }

//     /**
//      * Update the specified resource in storage.
//      */
//   public function update(Request $request, $id)
// {
//     try {
//         $slider = Sliders::findOrFail($id); // 👈 lấy thủ công từ ID

//         $data = $request->validate([
//             'name' => 'string|max:255',
//             'description' => 'string',
//             'position' => 'string',
//             'status' => 'boolean',
//         ]);

//         $slider->update($data);

//         return response()->json([
//             'message' => 'Update successful',
//             'slider' => $slider->fresh()
//         ]);
//     } catch (\Illuminate\Validation\ValidationException $e) {
//         return response()->json(['message' => 'Validation error: ' . $e->getMessage()], 422);
//     } catch (\Exception $e) {
//         return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
//     }
// }



//     /**
//      * Remove the specified resource from storage.
//      */
//     public function destroy($id)
//     {
//         try {
//             Sliders::destroy($id);
//             return response()->json(['message' => 'Deleted'], 200);
//         } catch (\Exception $e) {
//             Log::error(__CLASS__ . ' @ ' . $e->getMessage() . ' Line: ' . $e->getLine());
//             return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
//         }
//     }
}
