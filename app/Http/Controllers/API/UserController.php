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

class UserController extends Controller
{
    /**
     * Display a listing of users with pagination.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        $sortBy = $request->query('sort_by', 'u_id');
        $sortOrder = $request->query('sort_order', 'asc');
        $search = $request->query('search', '');
    
        $query = User::with(['division', 'position', 'roles']);
    
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('u_name', 'like', "%$search%")
                  ->orWhere('u_email', 'like', "%$search%")
                  ->orWhere('u_employee_id', 'like', "%$search%");
            });
        }

        // Validasi kolom yang boleh diurutkan
        $allowedSortColumns = ['u_employee_id', 'u_name', 'u_email', 'u_join_date'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'u_id';
        }

        $users = $query->orderBy($sortBy, $sortOrder)
        ->paginate($perPage);

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
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'u_employee_id' => 'required|string|max:20|unique:login.users,u_employee_id',
            'u_name' => 'required|string|max:100',
            'u_email' => 'required|email|max:100|unique:login.users,u_email',
            'u_password' => 'required|string|min:8|confirmed',
            'u_phone' => 'nullable|string|max:20',
            'u_address' => 'nullable|string',
            'u_birthdate' => 'nullable|date',
            'u_join_date' => 'required|date',
            'u_profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'u_division_id' => 'nullable|exists:login.divisions,div_id',
            'u_position_id' => 'nullable|exists:login.positions,pos_id',
            'u_is_manager' => 'nullable|boolean',
            'u_manager_id' => 'nullable|exists:login.users,u_id',
            'u_is_active' => 'nullable|boolean',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:login.roles,role_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        // Handle profile image upload
        if ($request->hasFile('u_profile_image')) {
            $file = $request->file('u_profile_image');
            $filename = $file->getClientOriginalName();
            $path = $file->storeAs('profile_images', $filename, 'public');
            $validated['u_profile_image'] = $path;
        }

        $validated['u_password'] = Hash::make($validated['u_password']);
        $validated['u_created_by'] = auth()->id() ?? 'system';
        $validated['u_updated_at'] = NULL;
        $validated['u_updated_by'] = NULL;

        $user = User::create($validated);
        $user->timestamps = false;

        // Attach roles if provided
        if (isset($validated['roles'])) {
            $user->roles()->attach($validated['roles'], [
                'ur_created_by' => auth()->id() ?? 'system'
            ]);
        }

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
     */
    public function show($id)
    {
        $user = User::with(['division', 'position', 'roles', 'manager'])
            ->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

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
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'u_employee_id' => ['required', 'string', 'max:20', Rule::unique('login.users', 'u_employee_id')->ignore($id, 'u_id')],
            'u_name' => 'required|string|max:100',
            'u_email' => ['required', 'email', 'max:100', Rule::unique('login.users', 'u_email')->ignore($id, 'u_id')],
            'u_password' => 'nullable|string|min:8',
            'u_phone' => 'nullable|string|max:20',
            'u_address' => 'nullable|string',
            'u_birthdate' => 'nullable|date',
            'u_join_date' => 'required|date',
            'u_profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'u_division_id' => 'nullable|exists:login.divisions,div_id',
            'u_position_id' => 'nullable|exists:login.positions,pos_id',
            'u_is_manager' => 'nullable|boolean',
            'u_manager_id' => 'nullable|exists:login.users,u_id',
            'u_is_active' => 'nullable|boolean',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:login.roles,role_id',
        ]);

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
        
        // Handle profile image upload
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

        $user->update($validated);

        // Sync roles if provided
        if (isset($validated['roles'])) {
            $user->roles()->sync($validated['roles']);
        }

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
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Detach all roles
        $user->roles()->detach();
        
        // Delete user
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }
}
