<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = ['name', 'display_name'];

    // Quan hệ: 1 Role có nhiều User
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // Quan hệ: 1 Role có nhiều Permission (qua bảng role_has_permissions)
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_has_permissions');
    }
}
