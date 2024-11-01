<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SebastianBergmann\Type\FalseType;

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
                $path = parse_url($old_url, PHP_URL_PATH);
                $relativePath = str_replace('storage/', 'public/', ltrim($path, '/'));
                Storage::delete($relativePath);
            }
            return $url;
        } catch (\Throwable $throwable) {
            Log::warning("Unable to upload file" ,["error" => $throwable]);
            return null;
        }

    }


    public function deleteFile($url)
    {
        try {
            if($url){
                $path = parse_url($url, PHP_URL_PATH);
                $relativePath = str_replace('storage/', 'public/', ltrim($path, '/'));
                Storage::delete($relativePath);
            }
            return true;
        } catch (\Throwable $th) {
            Log::warning('error message', [
                'error in deleting image' => $th
            ]);
        }

        return false;
    }


}
