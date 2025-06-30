<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SliderImage extends Model
{
    use HasFactory;

    protected $table = 'slider_images';

    protected $fillable = [
        'slider_id',
        'image_url',
        'link_url',
        'start_date',
        'end_date',
        'status',
        'display_order',
    ];

    public function slider()
    {
        return $this->belongsTo(Slider::class);
    }
}
