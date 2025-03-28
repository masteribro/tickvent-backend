<?php
namespace App\Services;

use App\Mail\RoleAssignmentMail;
use App\Models\Event;
use App\Models\EventRolesAssignee;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RolePermissionService {

    public function addRoleAndPermissionEvent($role_payload)
    {
        try {

            DB::beginTransaction();

            $role = Role::updateOrCreate([
                'slug' => $role_payload['name'],
                'event_id' => $role_payload['event_id']
            ],[
                'name' => $role_payload['name'],
                'description' => $role_payload['description'],
            ]);

            $res = $this->addPermissionsToRole($role, $role_payload['permissions']);

            if($res['status']) {
                DB::commit();

                $role->refresh();

                return [
                    'status' => true,
                    'data' => $role
                ];
            }


        } catch (\Throwable $th) {
            DB::rollBack();
            Log::warning('Error in creating role', [
                'error' => $th
            ]);
        }

        return [
            'status' => false
        ];
    }

    public function getEvent($event_id)
    {
        return Event::find('id', $event_id);
    }

    public function addPermissionsToRole($role, $permissions)
    {
        try {
            DB::beginTransaction();

            collect($permissions)->each(function ($permission) use ($role) {
                    Permission::updateOrCreate(
                        [
                            'slug'=> str()->slug($permission),
                            'role_id' => $role->id
                        ],[
                            'description' => $permission
                        ]
                    );
            });
            DB::commit();


            return [
                'status' => true
            ];

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::warning('Error in creating role', [
                'error' => $th
            ]);
        }

        return [
            'status' => false
        ];
    }

    public function  getRoles($event_id)
    {
        try {
            $roles = Role::where('event_id', $event_id)->get();

            return [
                'status' => true,
                'data' => $roles
            ];
        } catch (\Throwable $th) {
            Log::warning("error in adding role to event",[
                '' => $th->getMessage() . ' on line '. $th->getLine() . ' in ' . $th->getFile()
            ]);
        }
        return [
            'status' => false
        ];
    }

    public function assignRole($assignment_payload)
    {
        try {
            DB::beginTransaction();
            $assignee = EventRolesAssignee::updateOrCreate([
                'id' => $assignment_payload['id'] ?? null,
                'role_id' => $assignment_payload['role_id'],
                'event_id' => $assignment_payload['event_id'],
            ],[
                'email' => $assignment_payload['email'] ?? null,
                'phone_number' => $assignment_payload['phone'] ?? null,
                'name' => $assignment_payload['name'],
                'note' => $assignment_payload['note']
            ]);
            $assignee->refresh();

            if($assignee->email) {
                Mail::to($assignee->email )->send(new RoleAssignmentMail($assignee));
            } else {
                $resp = (new SmsMessage)->send($assignee->phone_number, "Tickvent", 'You have been assigned the role of ' . $assignee->role->name );
            }

            DB::commit();

            return [
                'status' => true,
                'data' => $assignee
            ];
        } catch (\Throwable $th) {
            Log::warning("error in adding role to event",[
                '' => $th->getMessage() . ' on line '. $th->getLine() . ' in ' . $th->getFile()
            ]);
        }
        return [
            'status' => false
        ];
    }

    public function deleteRole($role_ids)
    {
        try {
            $roles = Role::whereIn('id', $role_ids)->get();
            $roles->each(function ($role) {
                $role->permissions->each->delete();
                $role->assignees->each->delete();
            });

            $roles->each->delete();
            return [
                'status' => true
            ];
        } catch(\Throwable $th) {
            Log::warning("error in delete role in event",[
                '' => $th->getMessage() . ' on line '. $th->getLine() . ' in ' . $th->getFile()
            ]);
        }
        return [
            'status' => false
        ];

    }
}
