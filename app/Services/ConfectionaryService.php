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

            $confectionary = $this->createConfectionary($payload["confectionary_name"], $payload["confectionary_price"], $payload["event_id"], $payload["category"],$payload["confectionary_images"]);

            if($confectionary) {
                if(isset($payload["confectionary_additions"])) {
                    $additions = $this->addAddtionsToConfectionary($payload["confectionary_additions"], $confectionary);

                    if(!$additions) {
                        Log::warning("Unable to add confectionaries");
                    }
                }

                $confectionary = Confectionary::with(['attachments','images'])->find($confectionary->id);

                return [
                    'status' => true,
                    'data' => $confectionary,
                    'message' => "Confectionary Added to events successfull"
                ];
            }

        } catch (\Throwable $th) {
            Log::warning("Error in adding confectionary to event",[
                'error' => $th
            ]);
        }

        return [
            'status' => true,
            'message' => "Unable to add confectionary"
        ];
    }

    private function createConfectionary($name,$price,$event_id,$category,$images)
    {
        DB::beginTransaction();
        try {
            $confectionary = Confectionary::updateOrCreate([
                'slug' => str()->slug($name, '-'),
                'event_id' => $event_id
            ], [
                'category' => implode(',', $category),
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
            DB::beginTransaction();
            foreach($additions as $addition) {
                $attachment = ConfectionaryAttachment::updateOrCreate([
                    'slug' => $addition["name"],
                    "confectionary_id" => $confectionary['id']
                ], [
                    'name' => $addition["name"],
                    'price' => $addition["price"]
                ]);

                $url = $this->fileUploadService->uploadFile('confect-attachments',$addition["image"], $attachment["image_url"], $attachment["name"]);

                $attachment->update([
                    'image_url' => $url
                ]);
            }

            DB::commit();

            return true;
        } catch(\Throwable $th) {
            Log::warning("error in saving confectionary attachment", [
                'error' => $th
            ]);
            DB::rollBack();
        }

        return false;
    }

    public function getConfectionary($event_id, $allOrId)
    {
        try {
            $confectionaries = Confectionary::with(['attachments','images'])->orwhere("event_id", $event_id);

            $confectionaries = $confectionaries->when(!($allOrId == 'all'), function($query) use ($allOrId) {
                return $query->where('id', $allOrId);
            });

            if(!($allOrId == 'all')) {
               $confectionaries =  $confectionaries->first();
            } else {
               $confectionaries = $confectionaries->get();
            }

            if(!$confectionaries) {
                return [
                    'status' => false,
                    'message' => 'Confectionary not found'
                ];
            }
            return [
                'status' => true,
                'data' => $confectionaries,
            ];

            }catch(\Throwable $th) {
            Log::warning("Error in trying to get confection in service", [
                "" => $th
            ]);
        }

        return [
            'status' => false,
            'message' => 'An error occured while trying to get confectionary. Plese try again later'
        ];


    }

    public function updateEventConfectionary($event_id, $confectionary_id,$data)
    {
        $confectionary = $this->getConfectionary($event_id, $confectionary_id);
        if(!$confectionary['status']) {
            return [
                'status' => false,
                'message' => $confectionary['message']
            ];
        }

        $confectionary['data']->update($data);


       return $confectionary = $confectionary['data'];

    }

}



?>
