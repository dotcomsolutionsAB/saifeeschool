<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class PermissionRoleController extends Controller
{
    //
     /**
     * Create a single permission with validity
     */
    public function createPermission(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:permissions,name',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
        ]);

        $permission = Permission::create([
            'name' => $request->name,
            'guard_name' => 'sanctum',
            'valid_from' => $request->valid_from,
            'valid_to' => $request->valid_to,
        ]);

        return response()->json(['message' => 'Permission created successfully', 'permission' => $permission], 201);
    }

    /**
     * Create bulk permissions with validity
     */
    public function createBulkPermissions(Request $request)
    {
        $request->validate([
            'permissions' => 'required|array',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
        ]);

        $createdPermissions = [];
        $existingPermissions = [];

        foreach ($request->permissions as $permissionName) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionName],
                [
                    'guard_name' => 'sanctum',
                    'valid_from' => $request->valid_from,
                    'valid_to' => $request->valid_to,
                ]
            );

            if ($permission->wasRecentlyCreated) {
                $createdPermissions[] = $permission;
            } else {
                $existingPermissions[] = $permissionName;
            }
        }

        return response()->json([
            'message' => 'Bulk permissions processed successfully',
            'created_permissions' => $createdPermissions,
            'existing_permissions' => $existingPermissions
        ]);
    }

    /**
     * Create a role with validity
     */
    public function createRole(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
           
        ]);

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'sanctum',
           
        ]);

        return response()->json(['message' => 'Role created successfully', 'role' => $role], 201);
    }

    /**
     * Add permissions to a role with validity
     */
    public function addPermissionsToRole(Request $request)
    {
        $request->validate([
            'role' => 'required|string',
            'permissions' => 'required|array',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
        ]);

        $role = Role::where('name', $request->role)->first();
        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        foreach ($request->permissions as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            $role->givePermissionTo($permission);

            // Attach validity to the pivot table
           
        }

        return response()->json(['message' => 'Permissions added to role successfully', 'role' => $role], 200);
    }

    /**
     * Assign permissions to a user (model) with validity
     */
    public function assignPermissionsToUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'permissions' => 'required|array',
            'permissions.*.name' => 'required|string|exists:permissions,name',
            // 'permissions.*.valid_from' => 'nullable|date',
            // 'permissions.*.valid_to' => 'nullable|date|after_or_equal:permissions.*.valid_from',
            // 'sub_sector_ids' => 'required|array', // Always required and an array
            // 'sub_sector_ids.*' => 'required|integer|exists:t_sub_sector,id', // Validate sub-sector IDs
        ]);
    
        try {
            $user = User::findOrFail($request->user_id);
    
            // Fetch sector_ids based on the provided sub_sector_ids
            // $sectorIds = \DB::table('t_sub_sector')
            //     ->whereIn('id', $request->sub_sector_ids)
            //     ->distinct()
            //     ->pluck('sector_id')
            //     ->toArray();
    
            // Store sector and sub-sector access in the users table
            // $user->update([
            //     'sector_access_id' => json_encode($sectorIds),
            //     'sub_sector_access_id' => json_encode($request->sub_sector_ids),
            // ]);
    
            foreach ($request->permissions as $permissionData) {
                $permission = Permission::where('name', $permissionData['name'])->first();
    
                if ($permission) {
                    // Assign permission to user
                    $user->givePermissionTo($permission);
    
                    // Update model_has_permissions with validity dates
                    \DB::table('model_has_permissions')->updateOrInsert(
                        [
                            'model_id' => $user->id,
                            'model_type' => get_class($user),
                            'permission_id' => $permission->id,
                        ]
                        // ,
                        // [
                        //     'valid_from' => $permissionData['valid_from'] ?? null,
                        //     'valid_to' => $permissionData['valid_to'] ?? null,
                        // ]
                    );
                }
            }
    
            return response()->json([
                'message' => 'Permissions access assigned successfully.',
                'user_id' => $user->id,
                'permissions' => $request->permissions,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while assigning permissions.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Resolve IDs from the table based on the provided array or 'all'.
     *
     * @param string $table
     * @param array $ids
     * @return array
     */
    
    /**
     * Resolve sector IDs based on input
     */
   

    /**
     * Get valid permissions for a user
     */
    public function getUserPermissions($userId)
    {
        $user = User::findOrFail($userId);

        // Fetch permissions with validity conditions
        $permissions = $user->permissions()
            // ->where(function ($query) {
            //     $query->whereNull('valid_from')
            //         ->orWhere('valid_from', '<=', now());
            // })
            // ->where(function ($query) {
            //     $query->whereNull('valid_to')
            //         ->orWhere('valid_to', '>=', now());
            // })
            ->get();

        // Include sector and sub-sector access IDs
        $sectorAccessIds = json_decode($user->sector_access_id, true) ?? [];
        $subSectorAccessIds = json_decode($user->sub_sector_access_id, true) ?? [];

        return response()->json([
            'user' => $user->makeHidden(['created_at', 'updated_at']),
            'permissions' => $permissions->makeHidden(['created_at', 'updated_at']),
            'sector_access_ids' => $sectorAccessIds,
            'sub_sector_access_ids' => $subSectorAccessIds,
        ], 200);
    }

    /**
     * Get valid permissions for a role
     */
    public function getRolePermissions($roleName)
    {
        // Fetch the role by name
        $role = Role::where('id', $roleName)->first();
    
        // If the role is not found, return a 404 error
        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }
    
        // Retrieve all permissions associated with the role
        $permissions = $role->permissions()->get();
    
        // Return the role and permissions in the response
        return response()->json([
            'role' => $role,
            'permissions' => $permissions
        ], 200);
    }

    public function removePermissionsFromUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'permissions' => 'nullable|array', // Permissions array is optional
            'permissions.*.name' => 'required|string|exists:permissions,name',
            'sub_sector_ids' => 'nullable|array', // Sub-sector IDs array is optional
            'sub_sector_ids.*' => 'required|integer|exists:t_sub_sector,id',
        ]);

        try {
            $user = User::findOrFail($request->user_id);

            // Handle permissions removal if provided
            if (!empty($request->permissions)) {
                foreach ($request->permissions as $permissionData) {
                    $permission = Permission::where('name', $permissionData['name'])->first();
                    
                    if ($permission) {
                        // Revoke permission from user
                        $user->revokePermissionTo($permission);

                        // Remove validity details from model_has_permissions
                        \DB::table('model_has_permissions')->where([
                            'model_id' => $user->id,
                            'model_type' => get_class($user),
                            'permission_id' => $permission->id,
                        ])->delete();
                    }
                }
            }

            // Handle sub-sector access removal if provided
            if (!empty($request->sub_sector_ids)) {
                // Decode existing sub-sector and sector access
                $existingSubSectors = json_decode($user->sub_sector_access_id, true) ?? [];
                $existingSectors = json_decode($user->sector_access_id, true) ?? [];

                // Filter out the sub-sector IDs to be removed
                $updatedSubSectors = array_diff($existingSubSectors, $request->sub_sector_ids);

                // Fetch the remaining sector IDs based on updated sub-sectors
                $updatedSectors = \DB::table('t_sub_sector')
                    ->whereIn('id', $updatedSubSectors)
                    ->distinct()
                    ->pluck('sector_id')
                    ->toArray();

                // Update the user's sector and sub-sector access
                $user->update([
                    'sector_access_id' => json_encode($updatedSectors),
                    'sub_sector_access_id' => json_encode($updatedSubSectors),
                ]);
            }

            return response()->json([
                'message' => 'Permissions and sector/sub-sector access removed successfully.',
                'user_id' => $user->id,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while removing permissions.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function getAllPermissions(Request $request)
    {
        // Fetch all permissions, optionally paginate
        $permissions = Permission::all(); // You can replace `all` with `paginate($perPage)` if needed.

        // Return response
        return response()->json([
            'success' => true,
            'permissions' => $permissions
        ], 200);
    }
    public function getAllRoles(Request $request)
    {
        // Fetch all permissions, optionally paginate
        $roles = Role::all(); // You can replace `all` with `paginate($perPage)` if needed.

        // Return response
        return response()->json([
            'success' => true,
            'roles' => $roles
        ], 200);
    }
    public function createRoleWithPermissions(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'nullable|array',
            
            'remarks' => 'nullable|string|max:255',
        ]);

        // Retrieve jamiat_id from the authenticated user
        $jamiatId = auth()->user()->jamiat_id;

        // Create the role
        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'sanctum',
            'jamiat_id' => $jamiatId,
            'remarks' => $request->remarks,
        ]);

        // Add permissions if provided
        if (!empty($request->permissions)) {
            foreach ($request->permissions as $permissionName) {
                $permission = Permission::firstOrCreate(['name' => $permissionName]);
                $role->givePermissionTo($permission);

                // Handle validity if provided
            
            }
        }

        return response()->json([
            'message' => 'Role created successfully with permissions',
            'role' => $role,
        ], 201);
    }
    public function getUsersWithPermissions()
    {
        $users = DB::table('users')
            ->join('model_has_permissions', 'users.id', '=', 'model_has_permissions.model_id')
            ->join('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
            ->select(
                'users.id as user_id',
                'users.name as user_name',
                'users.email as user_email',
                'users.role as user_role',
                // 'users.jamiat_id',
                'permissions.name as permission_name',
                // 'model_has_permissions.valid_to as validity'
            )
            ->orderBy('users.name', 'asc')
            ->get();

        // Group permissions by user
        $groupedUsers = $users->groupBy('user_id')->map(function ($userGroup) {
            $user = $userGroup->first(); // Get user details
            return [
                'user_id' => $user->user_id,
                'user_name' => $user->user_name,
                'user_email' => $user->user_email,
                'user_role' => $user->user_role,
                // 'jamiat_id' => $user->jamiat_id,
                'permissions' => $userGroup->map(function ($permission) {
                    return [
                        'permission_name' => $permission->permission_name,
                        // 'valid_to' => $permission->validity,
                    ];
                })->unique('permission_name')->values(),
            ];
        })->values();

        return response()->json([
            'message' => 'Users with grouped permissions retrieved successfully.',
            'data' => $groupedUsers,
        ], 200);
    }
}
