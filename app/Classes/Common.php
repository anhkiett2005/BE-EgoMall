<?php
namespace App\Classes;

use App\Exceptions\ApiException;
use Illuminate\Support\Facades\Http;

class Common {

    public static function generateVariantName(string $parentName, array $options)
    {
        // Lấy ra tất cả value từ options
        $values = array_values($options);

        // Ghép các giá trị với tên cha
        $variantName = $parentName . ' - ' . implode(' - ', $values);

        return $variantName;
    }

        public static function uploadImageToCloudinary($file): ?string
    {
        $cloudName = 'dnj08gvqi';
        $uploadPreset = 'upload-egomall';
        $apiKey = '2jBRbJSnVeE6ZKLR3npXonsOQuA';

        $response = Http::asMultipart()->post("https://api.cloudinary.com/v1_1/{$cloudName}/image/upload", [
            ['name' => 'file', 'contents' => fopen($file->getRealPath(), 'r')],
            ['name' => 'upload_preset', 'contents' => $uploadPreset],
            ['name' => 'api_key', 'contents' => $apiKey],
        ]);

        if (!$response->successful()) {
            throw new ApiException('Upload ảnh thất bại', 500, [$response->body()]);
        }

        return $response->json()['secure_url'] ?? null;
    }
}
