<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Permission extends Model
{
    use HasFactory;

    protected $table = 'permissions';

    protected $fillable = [
        'name',
        'display_name',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class,'role_has_permissions','permission_id','role_id');
    }
}
