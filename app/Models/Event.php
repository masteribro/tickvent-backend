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

    public function user() {
        return $this->belongsTo(User::class);
    }
    
    public function getNameAttribute($value) 
    {
        return Str::title($value);
    }
    public function getImagesAttribute() 
    {
        return EventImage::where("event_id", $this->id)->get();
    }

    public function getEventOrganizerAttribut()

    {
        return EventOrganizer::where("id", $this->organizer_id)->first();
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
