<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $table = 'system_settings';

    protected $fillable = [
        'setting_key',
        'setting_value',
        'setting_type',
        'setting_group',
        'setting_label',
        'description',
    ];

    // Tuỳ chọn: constant cho FE/BE tham chiếu
    public const TYPES = ['string','text','boolean','number','email','url','image','password','json'];
    public const GROUPS = ['general','email','system','contact','seo','chatbot','integrations'];

    // scope lọc theo group (xài sau)
    public function scopeGroup($q, string $group)
    {
        return $q->where('setting_group', $group);
    }
}
