<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileUploadService
{

    public function uploadFile($folder, $file ,$old_url, $name) {
        try {
            $ext = $file->getClientOriginalExtension();
            $time = time();
            $user_name = str()->slug($name, '-');
            $fileName = "{$user_name}{$time}.{$ext}";
            //
            $filePath = $file->storeAs($folder, $fileName ,'public');
            $url = config('app.url') . Storage::url($filePath);
            if($old_url){
                Storage::delete($old_url);
            }
            return $url;
        } catch (\Throwable $throwable) {
            Log::warning("Unable to upload file" ,["error" => $throwable]);
            return null;
        }
    }


}
