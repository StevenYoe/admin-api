<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Division;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

// DivisionController handles CRUD operations and queries for Division resources via API endpoints.
class DivisionController extends Controller
{
    /**
     * Display a listing of divisions with pagination.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     * This method returns a paginated list of divisions, allowing sorting by specific columns.
     */
    public function index(Request $request)
    {
        // Get pagination and sorting parameters from the request
        $perPage = $request->query('per_page', 10);
        $sortBy = $request->query('sort_by', 'div_id');
        $sortOrder = $request->query('sort_order', 'asc');

        // Only allow sorting by certain columns
        $allowedSortColumns = ['div_id', 'div_code', 'div_name'];
        $sortBy = in_array($sortBy, $allowedSortColumns) ? $sortBy : 'div_id';

        // Query divisions with sorting and pagination
        $divisions = Division::orderBy($sortBy, $sortOrder)
            ->paginate($perPage);

        // Return paginated divisions as JSON
        return response()->json([
            'success' => true,
            'data' => $divisions,
        ]);
    }

    /**
     * Get all active divisions without pagination.
     *
     * @return \Illuminate\Http\Response
     *
     * This method returns all divisions that are marked as active.
     */
    public function all()
    {
        // Query all active divisions
        $divisions = Division::where('div_is_active', true)->get();

        // Return active divisions as JSON
        return response()->json([
            'success' => true,
            'data' => $divisions,
        ]);
    }

    /**
     * Store a newly created division.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     * This method validates and creates a new division record.
     */
    public function store(Request $request)
    {
        // Validate the request data for creating a division
        $validator = Validator::make($request->all(), [
            'div_code' => 'required|string|max:10|unique:login_divisions,div_code',
            'div_name' => 'required|string|max:100',
            'div_is_active' => 'nullable|boolean',
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
        $validated['div_created_by'] = auth()->id() ?? 'system';
        $validated['div_updated_by'] = auth()->id() ?? 'system';

        // Create the division
        $division = Division::create($validated);

        // Return success response with created division
        return response()->json([
            'success' => true,
            'message' => 'Division created successfully',
            'data' => $division
        ], 201);
    }

    /**
     * Display the specified division.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     *
     * This method returns a division by its ID, including its users and their positions.
     */
    public function show($id)
    {
        // Find division with related users and their positions
        $division = Division::with('users', 'users.position')->find($id);

        // Return error if division not found
        if (!$division) {
            return response()->json([
                'success' => false,
                'message' => 'Division not found'
            ], 404);
        }

        // Return division data as JSON
        return response()->json([
            'success' => true,
            'data' => $division
        ]);
    }

    /**
     * Update the specified division.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     *
     * This method validates and updates an existing division record.
     */
    public function update(Request $request, $id)
    {
        // Find the division to update
        $division = Division::find($id);

        // Return error if division not found
        if (!$division) {
            return response()->json([
                'success' => false,
                'message' => 'Division not found'
            ], 404);
        }

        // Validate the request data for updating a division
        $validator = Validator::make($request->all(), [
            'div_code' => [
                'required', 'string', 'max:10',
                Rule::unique('login_divisions', 'div_code')->ignore($id, 'div_id')
            ],
            'div_name' => 'required|string|max:100',
            'div_is_active' => 'nullable|boolean',
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
        $validated['div_updated_by'] = auth()->id() ?? 'system';

        // Update the division
        $division->update($validated);

        // Return success response with updated division
        return response()->json([
            'success' => true,
            'message' => 'Division updated successfully',
            'data' => $division
        ]);
    }

    /**
     * Remove the specified division.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     *
     * This method deletes a division if it exists and has no associated users.
     */
    public function destroy($id)
    {
        // Find the division to delete
        $division = Division::find($id);

        // Return error if division not found
        if (!$division) {
            return response()->json([
                'success' => false,
                'message' => 'Division not found'
            ], 404);
        }

        // Prevent deletion if division has users
        if ($division->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete division. It has associated users.'
            ], 400);
        }

        // Delete the division
        $division->delete();

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Division deleted successfully'
        ]);
    }
}
