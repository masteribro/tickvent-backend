<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
