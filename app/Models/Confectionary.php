<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Confectionary extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];


    public function attachments()
    {
        return $this->hasMany(ConfectionaryAttachment::class);
    }

    public function setCategoryAttribute($value)
    {
        $this->attributes['category'] = is_array($value) ? implode(',', $value) : $value;
    }

        public function getCategoryAttribute($value)
    {
        return $value ? explode(',', $value) : [];
    }


    public function images()
    {
        return $this->hasMany(ConfectionaryImage::class);
    }

}
