<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class SystemSettingResource extends JsonResource
{
    public function toArray($request)
    {
        $value = $this->setting_value;

        // Mask password
        if ($this->setting_type === 'password') {
            $value = '******';
        }

        // (Tuỳ chọn) Cast để FE hiển thị đúng mà không cần đoán
        if ($this->setting_type === 'boolean') {
            $value = (bool) ((string)$this->setting_value === '1' || $this->setting_value === 1 || $this->setting_value === true);
        } elseif ($this->setting_type === 'number') {
            $value = is_numeric($this->setting_value) ? $this->setting_value + 0 : $this->setting_value;
        } elseif ($this->setting_type === 'json') {
            $value = $this->setting_value ? json_decode($this->setting_value, true) : null;
        }
        
        return [
            'setting_key'   => $this->setting_key,
            'setting_value' => $this->setting_value,
            'setting_type'  => $this->setting_type,
            'setting_group' => $this->setting_group,
            'setting_label' => $this->setting_label,
            'description'   => $this->description,
            'updated_at'    => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}