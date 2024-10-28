<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Confectionary extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function attachments()
    {
        return $this->hasMany(ConfectionaryAttachment::class);
    }

    public function setCategoryAttribute($value){
        return implode(',', $value);
    }

    public function getCategoryAttribute()
    {
        return explode(',', $this->category);
    }
}
