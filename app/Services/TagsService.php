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

           $event->update(
                [ 'tags' => implode(',', $tags)
                ]
           );

        } catch (\Throwable $throwable) {
            Log::warning("Tags Error", [
                "error" => $throwable
            ]);
        }
    }
    
}