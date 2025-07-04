<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Blog extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'image_url',
        'status',
        'views',
        'category_id',
        'created_by',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    // Danh mục bài viết (lọc đúng loại "blog")
    public function category()
    {
        return $this->belongsTo(Category::class)->where('type', 'blog');
    }

    // Người tạo bài viết
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}