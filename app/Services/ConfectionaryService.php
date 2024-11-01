<?php

namespace App\Services;

use App\Helpers\ResponseHelper;
use App\Models\Confectionary;
use App\Models\ConfectionaryAttachment;
use App\Models\ConfectionaryImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
                        Log::warning("Unable to add att$attachments");
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

    public function updateEventConfectionary($event_id, $confectionary_id,$confectionaryPayload, $attachments)
    {
        try {

        $confectionary = $this->getConfectionary($event_id, $confectionary_id);
        if(!$confectionary['status']) {
            return [
                'status' => false,
                'message' => $confectionary['message']
            ];
        }

        $updated = $confectionary['data']->update(
            ['price' => $confectionaryPayload["price"]
        ]);

        $attachments = $this->updateAttachements($confectionary_id, $attachments);


        $confectionary = Confectionary::with(['attachments','images'])->find($confectionary_id);

            return [
                'status' => true,
                "data" => $confectionary
            ];

        } catch (\Throwable $th) {
            Log::warning("error response in updatng event confectionary", [
                'error' => $th
            ]);
        }

        return [
            'status' => true,
            "data" => 'Error while trying to update confectionary'
        ];

    }

    public function updateAttachements($confectionary_id, $attachments)
    {
        try {


            DB::beginTransaction();
            if(empty($attachments)) {
                return[
                    'status' => false
                ];
            }
            Log::warning("I go here");
            foreach($attachments as $attachment) {
                $confectionary_attachment = ConfectionaryAttachment::where('confectionary_id', $confectionary_id)->where('slug', str()->slug($attachment['name'], '-'))->orWhere('id', $attachment['id'] ?? null)->first();
                if($confectionary_attachment) {
                    $confectionary_attachment->update([
                        'price' => $attachment['price'],
                        'image_url' => $this->fileUploadService->uploadFile('confect-attachments',$attachment["image"], $confectionary_attachment["image_url"] ?? null, $attachment["name"])
                    ]);
                    Log::warning("log",[
                        '' => $confectionary_attachment
                    ]);
                } else {
                    ConfectionaryAttachment::create([
                        'price' => $attachment['price'],
                        'confectionary_id' => $confectionary_id,
                        'name' => $attachment['name'],
                        'slug' => str()->slug($attachment['name'], '-'),
                        'image_url' => $this->fileUploadService->uploadFile('confect-attachments',$attachment["image"], $attachment["image_url"] ?? null, $attachment["name"])
                    ]);
                }
            }

            DB::commit();

            return [
                'status' => true
            ];



        } catch(\Throwable $th) {
            DB::rollBack();
            Log::warning("update attachment error",[
                'error' => $th
            ]);
        }

        return [
            'status' => false
        ];

    }

    public function deleteConfectionary($confectionaries)
    {
        try{
            $confectionaries = Confectionary::with(['attachments', 'images'])->whereIn('id', $confectionaries)->get();

            collect($confectionaries)->each(function ($confectionary) {
                collect($confectionary->attachments)->each(function ($attachment) {
                    (new FileUploadService)->deleteFile($attachment->image_url);
                });

                collect($confectionary->images)->each(function ($image) {
                    (new FileUploadService)->deleteFile($image->image_url);
                });

                $confectionary->delete();

            });

            return [
                'status' => true
            ];
        } catch(\Throwable $th) {
            Log::warning("error in deleting confectionary",[
                '' => $th
            ]);
        }

        return [
            'status' => false
        ];
    }

    public function deleteConfectionaryAttachment($confectionary_id, $attachments_ids)
    {
        try{
            $attachments = ConfectionaryAttachment::where('confectionary_id', $confectionary_id)->whereIn('id', $attachments_ids)->get();

            Log::warning('',[
                '' => $attachments
            ]);
            collect($attachments)->each(function ($attachment) {
                (new FileUploadService)->deleteFile($attachment->image_url);
                $attachment->delete();
            });

            return [
                'status' => true
            ];
        } catch(\Throwable $th) {
            Log::warning("error in deleting confectionary attachment",[
                '' => $th
            ]);
        }

        return [
            'status' => false
        ];
    }

    public function deleteEventConfectionaryImages($confectionary_id, $images_ids)
    {
        try{
            $images = ConfectionaryImage::where('confectionary_id', $confectionary_id)->whereIn('id', $images_ids)->get();

            collect($images)->each(function ($image) {
                (new FileUploadService)->deleteFile($image->image_url);
                $image->delete();
            });

            return [
                'status' => true
            ];
        } catch(\Throwable $th) {
            Log::warning("error in deleting confectionary images",[
                '' => $th
            ]);
        }

        return [
            'status' => false
        ];
    }

}



?>
