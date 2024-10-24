<?php 
namespace App\Services;

use App\Models\Event;
use App\Models\EventTag;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class TagsService {

    public static function createTag( Event $event, array $tags) 
    {
        try {

            foreach($tags as $tag) {
                EventTag::firstOrCreate([
                    'event_id' => $event->id,
                    'name' => $tag,
                ],[
                    'event_id' => $event->id,
                    'name' => $tag
                ]);
    
                Tag::firstOrCreate([
                    'name' => strtolower($tag)
                ],[
                    'name' => strtolower($tag)
                ]);
            }
            
            Log::warning("Done saving tags");

        } catch (\Throwable $throwable) {
            Log::warning("Tags Error", [
                "error" => $throwable
            ]);
        }
    }
    
}