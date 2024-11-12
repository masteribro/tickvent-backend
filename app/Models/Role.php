<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    protected $with =[
        'permissions'
    ];

    public function permissions()
    {
        return $this->hasMany(Permission::class);
    }

    public function assignees()
    {
        return $this->hasMany(EventRolesAssignee::class);
    }
}
