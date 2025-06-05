<?php

namespace Database\Seeders;

use App\Models\VariantValue;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VariantValueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $values = [
            1 => ['Đỏ', 'Hồng', 'Cam', 'Nude', 'Mận', 'Nâu', 'Trong suốt', 'Đào'],
            2 => ['5ml', '10ml', '30ml', '50ml', '100ml', '150ml', '200ml'],
            3 => ['Hoa hồng', 'Cam chanh', 'Gỗ đàn hương', 'Trái cây', 'Thảo mộc', 'Không mùi'],
            4 => ['SPF15', 'SPF30', 'SPF50', 'SPF50+ PA++', 'SPF50+ PA+++'],
            5 => ['Kem', 'Gel', 'Bọt', 'Sữa dưỡng', 'Dầu', 'Thỏi', 'Xịt', 'Bột', 'Lỏng'],
        ];

        foreach ($values as $optionId => $valueList) {
            foreach ($valueList as $value) {
                VariantValue::create([
                    'option_id' => $optionId,
                    'value' => $value,
                ]);
            }
        }
    }
}
