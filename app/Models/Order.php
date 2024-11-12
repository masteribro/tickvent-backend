<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    
    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    protected $guarded = ['id'];
}