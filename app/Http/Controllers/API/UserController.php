<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Models\Division;
use App\Models\Position;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

// UserController handles CRUD operations and queries for User resources via API endpoints.
class UserController extends Controller
{
    /**
     * Display a listing of users with pagination.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     * This method returns a paginated list of users, with optional search and sorting.
     * It also loads related division, position, and roles for each user.
     */
    public function index(Request $request)
    {
        // Get pagination, sorting, and search parameters from the request
        $perPage = $request->query('per_page', 10);
        $sortBy = $request->query('sort_by', 'u_id');
        $sortOrder = $request->query('sort_order', 'asc');
        $search = $request->query('search', '');
    
        // Start query with related models
        $query = User::with(['division', 'position', 'roles']);
    
        // Apply search filter if provided
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('u_name', 'like', "%$search%")
                  ->orWhere('u_email', 'like', "%$search%")
                  ->orWhere('u_employee_id', 'like', "%$search%");
            });
        }

        // Only allow sorting by certain columns
        $allowedSortColumns = ['u_employee_id', 'u_name', 'u_email', 'u_join_date'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'u_id';
        }

        // Query users with sorting and pagination
        $users = $query->orderBy($sortBy, $sortOrder)
        ->paginate($perPage);

        // Return paginated users as JSON
        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * Store a newly created user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     * This method validates and creates a new user record, handles profile image upload,
     * hashes the password, and attaches roles if provided.
     */
    public function store(Request $request)
    {
        // Validate the request data for creating a user
        $validator = Validator::make($request->all(), [
            'u_employee_id' => 'required|string|max:20|unique:login_users,u_employee_id',
            'u_name' => 'required|string|max:100',
            'u_email' => 'required|email|max:100|unique:login_users,u_email',
            'u_password' => 'required|string|min:8|confirmed',
            'u_phone' => 'nullable|string|max:20',
            'u_address' => 'nullable|string',
            'u_birthdate' => 'nullable|date',
            'u_join_date' => 'required|date',
            'u_profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'u_division_id' => 'nullable|exists:login_divisions,div_id',
            'u_position_id' => 'nullable|exists:login_positions,pos_id',
            'u_is_manager' => 'nullable|boolean',
            'u_manager_id' => 'nullable|exists:login_users,u_id',
            'u_is_active' => 'nullable|boolean',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:login_roles,role_id',
        ]);

        // Return validation errors if any
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        // Handle profile image upload if provided
        if ($request->hasFile('u_profile_image')) {
            $file = $request->file('u_profile_image');
            $filename = $file->getClientOriginalName();
            $path = $file->storeAs('profile_images', $filename, 'public');
            $validated['u_profile_image'] = $path;
        }

        // Hash the password before saving
        $validated['u_password'] = Hash::make($validated['u_password']);
        $validated['u_created_by'] = auth()->id() ?? 'system';
        $validated['u_updated_at'] = NULL;
        $validated['u_updated_by'] = NULL;

        // Create the user
        $user = User::create($validated);
        $user->timestamps = false;

        // Attach roles if provided
        if (isset($validated['roles'])) {
            $user->roles()->attach($validated['roles'], [
                'ur_created_by' => auth()->id() ?? 'system'
            ]);
        }

        // Return success response with created user and relations
        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $user->load(['division', 'position', 'roles'])
        ], 201);
    }

    /**
     * Display the specified user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     *
     * This method returns a user by its ID, including division, position, roles, and manager.
     */
    public function show($id)
    {
        // Find user with related division, position, roles, and manager
        $user = User::with(['division', 'position', 'roles', 'manager'])
            ->find($id);

        // Return error if user not found
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Return user data as JSON
        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Update the specified user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     *
     * This method validates and updates an existing user record, handles profile image upload and deletion,
     * updates the password if provided, and syncs roles if provided.
     */
    public function update(Request $request, $id)
    {
        // Find the user to update
        $user = User::find($id);

        // Return error if user not found
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Validate the request data for updating a user
        $validator = Validator::make($request->all(), [
            'u_employee_id' => ['required', 'string', 'max:20', Rule::unique('login_users', 'u_employee_id')->ignore($id, 'u_id')],
            'u_name' => 'required|string|max:100',
            'u_email' => ['required', 'email', 'max:100', Rule::unique('login_users', 'u_email')->ignore($id, 'u_id')],
            'u_password' => 'nullable|string|min:8',
            'u_phone' => 'nullable|string|max:20',
            'u_address' => 'nullable|string',
            'u_birthdate' => 'nullable|date',
            'u_join_date' => 'required|date',
            'u_profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'u_division_id' => 'nullable|exists:login_divisions,div_id',
            'u_position_id' => 'nullable|exists:login_positions,pos_id',
            'u_is_manager' => 'nullable|boolean',
            'u_manager_id' => 'nullable|exists:login_users,u_id',
            'u_is_active' => 'nullable|boolean',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:login_roles,role_id',
        ]);

        // Return validation errors if any
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();
        
        // Store old profile image path if exists
        $oldProfileImage = $user->u_profile_image;
        
        // Handle profile image upload if provided
        if ($request->hasFile('u_profile_image')) {
            $file = $request->file('u_profile_image');
            $filename = $file->getClientOriginalName();
            $path = $file->storeAs('profile_images', $filename, 'public');
            $validated['u_profile_image'] = $path;
            
            // Delete old profile image if exists
            if ($oldProfileImage && file_exists(storage_path('app/public/' . $oldProfileImage))) {
                unlink(storage_path('app/public/' . $oldProfileImage));
            }
        }

        $validated['u_updated_by'] = auth()->id() ?? 'system';
        unset($validated['u_created_at']);
        unset($validated['u_created_by']);

        // Update password if provided
        if (isset($validated['u_password'])) {
            $validated['u_password'] = Hash::make($validated['u_password']);
        } else {
            unset($validated['u_password']);
        }

        // Update the user
        $user->update($validated);

        // Sync roles if provided
        if (isset($validated['roles'])) {
            $user->roles()->sync($validated['roles']);
        }

        // Return success response with updated user and relations
        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user->load(['division', 'position', 'roles'])
        ]);
    }

    /**
     * Remove the specified user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     *
     * This method deletes a user and detaches all roles.
     */
    public function destroy($id)
    {
        // Find the user to delete
        $user = User::find($id);

        // Return error if user not found
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Detach all roles from the user
        $user->roles()->detach();
        
        // Delete the user
        $user->delete();

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }
}
