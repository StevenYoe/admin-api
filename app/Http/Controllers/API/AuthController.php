<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

// AuthController handles user authentication processes such as login, getting the authenticated user, and logout.
class AuthController extends Controller
{
    /**
     * Login user and create token
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     * This method validates the login request, checks user credentials,
     * ensures the user is active, loads user relations, creates an auth token,
     * and returns user data with roles and token if successful.
     */
    public function login(Request $request)
    {
        // Validate the request input for email and password
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Return validation errors if any
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Convert email to lowercase before checking
        $email = strtolower($request->email);

        // Find user by email
        $user = User::where('u_email', $email)->first();

        // Check if user exists and is active
        if (!$user || !$user->u_is_active) {
            return response()->json([
                'success' => false,
                'message' => 'User does not exist or is not active'
            ], 401);
        }

        // Verify the password
        if (!Hash::check($request->password, $user->u_password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Load related models: division, position, and roles
        $user->load(['division', 'position', 'roles']);

        // Create a new authentication token for the user
        $token = $user->createToken('auth_token')->plainTextToken;

        // Get user roles as an array
        $roles = $user->roles()->pluck('role_name')->toArray();

        // Return successful login response with user data and token
        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'roles' => $roles,
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ]);
    }

    /**
     * Get authenticated user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     * This method returns the currently authenticated user's data
     * along with their division, position, and roles.
     */
    public function me(Request $request)
    {
        // Get the authenticated user
        $user = $request->user();
        // Load related models
        $user->load(['division', 'position', 'roles']);

        // Return user data
        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Logout user (revoke token)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     * This method revokes the current access token, effectively logging out the user.
     */
    public function logout(Request $request)
    {
        // Delete the current access token
        $request->user()->currentAccessToken()->delete();

        // Return logout success response
        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out'
        ]);
    }
}