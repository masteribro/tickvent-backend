<?php

namespace App\Services;

use App\Helpers\ResponseHelper;
use App\Models\Confectionary;
use App\Models\ConfectionaryAttachment;
use App\Models\ConfectionaryImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConfectionaryService
{
    public function __construct(protected FileUploadService $fileUploadService)
    {

    }

    public function addConfectionary($payload, $user)
    {
        try {

            $confectionary = $this->createConfectionary($payload["confectionary_name"], $payload["confectionary_price"], $payload["event_id"], $payload["images"]);

            if($confectionary) {
                if(isset($payload["confectionary_addtions"])) {
                    $addtions = $this->addAddtionsToConfectionary($payload["confectionary_addtions"], $confectionary);
                }

                return ResponseHelper::successResponse("Confectionary added successfully", $confectionary);
            }

        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    private function createConfectionary($name,$price,$event_id,$images)
    {
        DB::beginTransaction();
        try {
            $confectionary = Confectionary::updateOrCreate([
                'slug' => str()->slug($name, '-'),
                'event_id' => $event_id
            ], [
                'name' => $name,
                'price' => $price,
            ]);

            if(isset($images)) {
                foreach($images as $image) {
                    $url = $this->fileUploadService->uploadFile('confectionary-imgs',$image,'',$confectionary->name);
                    ConfectionaryImage::create([
                        "confectionary_id" => $confectionary->id,
                        "image_url" => $url
                    ]);
                }
            }

            DB::commit();

            return $confectionary;

        } catch(\Throwable $th) {
            DB::rollBack();
            Log::warning("Error in add confectary",[
                "error" => $th
            ]);
        }

        return null;
    }

    public function addAddtionsToConfectionary(array $additions, $confectionary)
    {
        try {
            foreach($additions as $addition) {
                $attachment = ConfectionaryAttachment::updateOrCreate([
                    'slug' => $addition["name"],
                    "confectionary_id" => $confectionary['id']
                ], [
                    'name' => $addition["name"],
                    'price' => $addition["price"]
                ]);

                $url = $this->fileUploadService->uploadFile('confect-attache',$addition["image"], $attachment["image_url"], $attachment["name"]);

                $attachment->update([
                    'image_url' => $url
                ]);
            }
            return true;
        } catch(\Throwable $th) {
            Log::warning("error in saving confectionary attachment", [
                'error' => $th
            ]);
        }

        return false;
    }

}



?>
