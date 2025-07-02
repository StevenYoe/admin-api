<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

// RoleController handles CRUD operations and queries for Role resources via API endpoints.
class RoleController extends Controller
{
    /**
     * Display a listing of roles with pagination.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     * This method returns a paginated list of roles, allowing sorting by specific columns.
     */
    public function index(Request $request)
    {
        // Get pagination and sorting parameters from the request
        $perPage = $request->query('per_page', 10);
        $sortBy = $request->query('sort_by', 'role_id');
        $sortOrder = $request->query('sort_order', 'asc');

        // Only allow sorting by certain columns
        $allowedSortColumns = ['role_id', 'role_name', 'role_level'];
        $sortBy = in_array($sortBy, $allowedSortColumns) ? $sortBy : 'role_id';

        // Query roles with user count, sorting, and pagination
        $roles = Role::withCount('users')
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage);

        // Return paginated roles as JSON
        return response()->json([
            'success' => true,
            'data' => $roles,
        ]);
    }

    /**
     * Get all active roles without pagination.
     *
     * @return \Illuminate\Http\Response
     *
     * This method returns all roles that are marked as active.
     */
    public function all()
    {
        // Query all active roles
        $roles = Role::where('role_is_active', true)->get();

        // Return active roles as JSON
        return response()->json([
            'success' => true,
            'data' => $roles,
        ]);
    }

    /**
     * Store a newly created role.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     * This method validates and creates a new role record.
     */
    public function store(Request $request)
    {
        // Validate the request data for creating a role
        $validator = Validator::make($request->all(), [
            'role_name' => 'required|string|max:50|unique:login_roles,role_name',
            'role_level' => 'required|integer|min:0|max:100000',
            'role_is_active' => 'nullable|boolean',
        ]);

        // Return validation errors if any
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Prepare validated data and set created/updated by
        $validated = $validator->validated();
        $validated['role_created_by'] = auth()->id() ?? 'system';
        $validated['role_updated_by'] = auth()->id() ?? 'system';

        // Create the role
        $role = Role::create($validated);

        // Return success response with created role
        return response()->json([
            'success' => true,
            'message' => 'Role created successfully',
            'data' => $role
        ], 201);
    }

    /**
     * Display the specified role.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     *
     * This method returns a role by its ID, including its users.
     */
    public function show($id)
    {
        // Find role with related users
        $role = Role::with('users')->find($id);

        // Return error if role not found
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found'
            ], 404);
        }

        // Return role data as JSON
        return response()->json([
            'success' => true,
            'data' => $role
        ]);
    }

    /**
     * Update the specified role.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     *
     * This method validates and updates an existing role record.
     */
    public function update(Request $request, $id)
    {
        // Find the role to update
        $role = Role::find($id);

        // Return error if role not found
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found'
            ], 404);
        }

        // Validate the request data for updating a role
        $validator = Validator::make($request->all(), [
            'role_name' => [
                'required', 'string', 'max:50',
                Rule::unique('login_roles', 'role_name')->ignore($id, 'role_id')
            ],
            'role_level' => 'required|integer|min:0|max:100000',
            'role_is_active' => 'nullable|boolean',
        ]);

        // Return validation errors if any
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Prepare validated data and set updated by
        $validated = $validator->validated();
        $validated['role_updated_by'] = auth()->id() ?? 'system';

        // Update the role
        $role->update($validated);

        // Return success response with updated role
        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully',
            'data' => $role
        ]);
    }

    /**
     * Remove the specified role.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     *
     * This method deletes a role if it exists and has no associated users.
     */
    public function destroy($id)
    {
        // Find the role to delete
        $role = Role::find($id);

        // Return error if role not found
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found'
            ], 404);
        }

        // Prevent deletion if role has users
        if ($role->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete role. It has associated users.'
            ], 400);
        }

        // Delete the role
        $role->delete();

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully'
        ]);
    }
}
