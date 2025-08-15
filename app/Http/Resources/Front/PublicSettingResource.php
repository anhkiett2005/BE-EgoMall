<?php

namespace App\Http\Resources\Front;

use Illuminate\Http\Resources\Json\JsonResource;

class PublicSettingResource extends JsonResource
{
    public function toArray($request)
    {
        // $this là mảng phẳng từ service
        return [
            'site_name'     => $this['site_name'] ?? null,
            'site_logo'     => $this['site_logo'] ?? null,
            'site_address'  => $this['site_address'] ?? null,
            'hotline'       => $this['hotline'] ?? null,
            'contact_email' => $this['contact_email'] ?? null,
            'tiktok_url'    => $this['tiktok_url'] ?? null,
            'instagram_url' => $this['instagram_url'] ?? null,
            'youtube_url'   => $this['youtube_url'] ?? null,
            'facebook_url'  => $this['facebook_url'] ?? null,
            'zalo_url'      => $this['zalo_url'] ?? null,
            'updated_at'    => $this['updated_at'] ?? null,
        ];
    }
}
