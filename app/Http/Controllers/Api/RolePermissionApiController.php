<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RolePermissionApiController extends Controller
{
    public function addRoleToEvent(Request $request, $event)
    {
        try {
            $validator = \Validator::make($request->all(),[
                "name" => "required|string|max:60",
                "description" => "integer|string|max:255",
                "permissions" => ["required", "array"],
                "permissions.*" => ['required'|'string'],
            ]);

            if($validator->fails()) {
                return ResponseHelper::errorResponse("Validation Error", $validator->errors());
            }

           $resp =  $this->rolePermissionService->addRoleToEvents($event);



            if($resp["status"]) {
                return ResponseHelper::successResponse("Confectionary Images deleted successfully");
            }

        } catch(\Throwable $th) {
            Log::warning("error in deleting confectionary",[
                '' => $th
            ]);
        }
        return ResponseHelper::errorResponse("Unable to delete confectionaries");

    }
}
