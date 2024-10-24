<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory;

    protected $guarded = [
        'id'
    ];

    public function getNameAttribute($value) 
    {
        return Str::title($value);
    }
    public function getImagesAttribute() 
    {
        return EventImage::where("event_id", $this->id)->get();
    }
}
