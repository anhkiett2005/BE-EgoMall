<?php
namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
use App\Models\Slider_images;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SliderImagesController extends Controller
{
    public function index()
    {
        try {
            $sliderImages = Slider_images::all();
            return response()->json(['slider_images' => $sliderImages]);
        } catch (\Exception $e) {
            Log::error(__CLASS__ . ' @ ' . $e->getMessage() . ' Line: ' . $e->getLine());
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request, $id)
    {
        try {
            $sliderImage = Slider_images::findOrFail($id);
        $data = $request->validate([
            'slider_id'     => 'required|exists:sliders,id',
            'image_url'     => 'required|string',
            'link_url'      => 'required|string',
            'start_date'    => 'date|before_or_equal:end_date',
            'end_date'      => 'date|after_or_equal:start_date',
            'status'        => 'boolean',
            'display_order' => 'required|integer',
        ]);

        $sliderImage = Slider_images::create($data);

        return response()->json([
            'message' => 'Slider image created successfully.',
            'slider_image' => $sliderImage
        ], 201);
    } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error(__CLASS__ . ' @ ' . $e->getMessage() . ' Line: ' . $e->getLine());
        return response()->json(['message' => 'Validation error: ' . $e->getMessage()], 422);
    } catch (\Exception $e) {
            Log::error(__CLASS__ . ' @ ' . $e->getMessage() . ' Line: ' . $e->getLine());
        return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
    }
    }

    public function show($id)
    {
        try {
            $sliderImage = Slider_images::findOrFail($id);
            return response()->json(['slider_image' => $sliderImage]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Banner not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $sliderImage = Slider_images::findOrFail($id);

            $data = $request->validate([
                'slider_id'     => 'exists:sliders,id',
                'image_url'     => 'string',
                'link_url'      => 'string',
                'start_date'    => 'date',
                'end_date'      => 'date|after_or_equal:start_date',
                'status'        => 'boolean',
                'display_order' => 'integer',
            ]);

            $sliderImage->update($data);

            return response()->json([
                'message' => 'Slider image updated successfully.',
                'slider_image' => $sliderImage
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error(__CLASS__ . ' @ ' . $e->getMessage() . ' Line: ' . $e->getLine());
            return response()->json(['message' => 'Slider image not found'. $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error(__CLASS__ . ' @ ' . $e->getMessage() . ' Line: ' . $e->getLine());
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $sliderImage = Slider_images::findOrFail($id);
            $sliderImage->delete();

            return response()->json(['message' => 'Slider image deleted']);
        } catch (ModelNotFoundException $e) {
            Log::error(__CLASS__ . ' @ ' . $e->getMessage() . ' Line: ' . $e->getLine());
            return response()->json(['message' => 'Slider image not found'], 404);
        }
    }
}
