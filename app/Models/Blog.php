<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Blog extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'image_url',
        'excerpt',
        'published_at',
        'status',
        'category_blog_id',
        'author_id',
        'view_count',
        'slug',
        'content'
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class,'category_blog_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class,'author_id');
    }
}
