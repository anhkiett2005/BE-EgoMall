<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'categories';

    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'description',
        'thumbnail',
        'is_active',
        'is_featured',
        'type'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    protected $hidden = [
        // 'id'
    ];

    /**
     * Quan hệ: Category cha
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Quan hệ: Danh sách Category con
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->with('children');
    }

    /**
     *  Scope: Chỉ danh mục gốc (không có parent_id)
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     *  Scope: Lọc theo brand
     */
    public function scopeByBrand($query, $brandId)
    {
        return $query->where('brand_id', $brandId);
    }

    /**
     *  Scope: Chỉ danh mục hoạt động
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     *  Scope: Chỉ danh mục nổi bật
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function categoryOptions()
    {
        return $this->hasMany(CategoryOption::class);
    }

    public function blogs()
    {
        return $this->hasMany(Blog::class,'category_blog_id');
    }
}
