<?php
namespace App\Classes;

class Common {

    public static function generateVariantName(string $parentName, array $options)
    {
        // Lấy ra tất cả value từ options
        $values = array_values($options);

        // Ghép các giá trị với tên cha
        $variantName = $parentName . ' - ' . implode(' - ', $values);

        return $variantName;
    }
}
