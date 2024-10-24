<?php 
namespace App\Services;

use App\Models\Event;
use App\Models\EventImage;
use Illuminate\Support\Facades\Log;

class EventImageService {

    public static function saveImages(Event $event, $images) 
    {
        try {
            if(is_array($images)) {
                foreach($images as $image) {
                    EventImage::create([
                        'event_id' => $event->id,
                        "image" => $image
                    ]);
                }
            } else {
                EventImage::create([
                    'event_id' => $event->id,
                        "image" => $images
                ]);
            }
            Log::warning("Done saving images");
        } catch(\Throwable $throwable) {
            
            Log::warning("Image creation error", [
                "error" =>$throwable
            ]);

        }

    }

    
}