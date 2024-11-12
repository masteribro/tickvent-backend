<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Services\RolePermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RolePermissionApiController extends Controller
{
    public function __construct(protected RolePermissionService $rolePermissionService)
    {

    }

    public function addRoleToEvent(Request $request, $event_id)
    {
        try {
            $validator = \Validator::make($request->all(),[
                "name" => "required|string|max:60",
                "description" => "required|string|max:255",
                "permissions" => ["required", "array"],
                "permissions.*" => ['required', 'string'],
            ]);

            if($validator->fails()) {
                return ResponseHelper::errorResponse("Validation Error", $validator->errors());
            }


            $role_payload = $request->all();
            $role_payload['event_id'] = $event_id;

           $resp =  $this->rolePermissionService->addRoleAndPermissionEvent($role_payload);

            if($resp["status"]) {
                return ResponseHelper::successResponse("Role added successfully",$resp['data']);
            }

        } catch(\Throwable $th) {
            Log::warning("error in adding role to event",[
                '' => $th->getMessage() . ' on line '. $th->getLine() . ' in ' . $th->getFile()
            ]);
        }
        return ResponseHelper::errorResponse("Unable to add role to events");

    }

    public function getRolesToEvent($event_id)
    {
        try {
            $roles = $this->rolePermissionService->getRoles($event_id);
            if($roles['status']) {
                return ResponseHelper::successResponse('Roles successfully fetched',$roles['data']);
            }
        } catch(\Throwable $th) {
            Log::warning("get roles error",[
                '' => $th->getMessage() . ' on line '. $th->getLine() . ' in ' . $th->getFile()
            ]);
        }
        return ResponseHelper::errorResponse("Unable get roles for this event");
    }

    public function assignRole(Request $request, $event_id)
    {
        try {
            $validator = \Validator::make($request->all(),[
                "name" => "required|string|max:60",
                'role_id' => [
                    'required',
                    'exists:roles,id',
                    function ($attribute, $value, $fail) use($event_id) {
                        // Check if the specific condition is met in the roles table
                        $role = DB::table('roles')->where('id', $value)->first();
                        Log::warning(" ",[
                            '' => $event_id
                        ]);
                    if (!$role || $role->event_id != $event_id ) {
                        $fail('The selected role_id is invalid');
                    }
                    },
                ],
                "email" => "required_if:phone,null|email|max:50",
                // "phone" => "required_if:email,null|regex:/0[7-9][0-1]\d{8}",
                "note" => ['nullable','string'],
            ]);

            if($validator->fails()) {
                return ResponseHelper::errorResponse("Validation Error", $validator->errors());
            }


            $assignment_payload = $request->all();
            $assignment_payload['event_id'] = $event_id;

           $resp =  $this->rolePermissionService->assignRole($assignment_payload);

            if($resp["status"]) {
                return ResponseHelper::successResponse("Role added successfully",$resp['data']);
            }

        } catch(\Throwable $th) {
            Log::warning("error in adding role to event",[
                '' => $th->getMessage() . ' on line '. $th->getLine() . ' in ' . $th->getFile()
            ]);
        }
        return ResponseHelper::errorResponse("Unable to add role to events");
    }

    public function deleteRole(Request $request, $event_id)
    {
        try {
            $validator = \Validator::make($request->all(),[
                'role_ids' => "required|array",
                'role_ids.*' => [
                    'required',
                    'exists:roles,id',
                    function ($attribute, $value, $fail) use($event_id) {
                        // Check if the specific condition is met in the roles table
                        $role = DB::table('roles')->where('id', $value)->first();
                        Log::warning(" ",[
                            '' => $event_id
                        ]);
                    if (!$role || $role->event_id != $event_id ) {
                        $fail('The selected role_id is invalid');
                    }
                    },
                ],
               ]);

            if($validator->fails()) {
                return ResponseHelper::errorResponse("Validation Error", $validator->errors());
            }


           $resp =  $this->rolePermissionService->deleteRole($request->role_ids);

            if($resp["status"]) {
                return ResponseHelper::successResponse("Roles Deleted successfully");
            }

        } catch(\Throwable $th) {
            Log::warning("error in deleting role",[
                '' => $th->getMessage() . ' on line '. $th->getLine() . ' in ' . $th->getFile()
            ]);
        }
        return ResponseHelper::errorResponse("Unable to delete role");

    }
}
