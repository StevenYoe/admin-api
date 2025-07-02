<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

// PositionController handles CRUD operations and queries for Position resources via API endpoints.
class PositionController extends Controller
{
    /**
     * Display a listing of positions with pagination.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     * This method returns a paginated list of positions, allowing sorting by specific columns.
     */
    public function index(Request $request)
    {
        // Get pagination and sorting parameters from the request
        $perPage = $request->query('per_page', 10);
        $sortBy = $request->query('sort_by', 'pos_id');
        $sortOrder = $request->query('sort_order', 'asc');

        // Only allow sorting by certain columns
        $allowedSortColumns = ['pos_id', 'pos_code', 'pos_name'];
        $sortBy = in_array($sortBy, $allowedSortColumns) ? $sortBy : 'pos_id';

        // Query positions with sorting and pagination
        $positions = Position::orderBy($sortBy, $sortOrder)
            ->paginate($perPage);

        // Return paginated positions as JSON
        return response()->json([
            'success' => true,
            'data' => $positions,
        ]);
    }

    /**
     * Get all active positions without pagination.
     *
     * @return \Illuminate\Http\Response
     *
     * This method returns all positions that are marked as active.
     */
    public function all()
    {
        // Query all active positions
        $positions = Position::where('pos_is_active', true)->get();

        // Return active positions as JSON
        return response()->json([
            'success' => true,
            'data' => $positions,
        ]);
    }

    /**
     * Store a newly created position.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     * This method validates and creates a new position record.
     */
    public function store(Request $request)
    {
        // Validate the request data for creating a position
        $validator = Validator::make($request->all(), [
            'pos_code' => 'required|string|max:10|unique:login_positions,pos_code',
            'pos_name' => 'required|string|max:100',
            'pos_is_active' => 'nullable|boolean',
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
        $validated['pos_created_by'] = auth()->id() ?? 'system';
        $validated['pos_updated_by'] = auth()->id() ?? 'system';

        // Create the position
        $position = Position::create($validated);

        // Return success response with created position
        return response()->json([
            'success' => true,
            'message' => 'Position created successfully',
            'data' => $position
        ], 201);
    }

    /**
     * Display the specified position.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     *
     * This method returns a position by its ID, including its users and their divisions.
     */
    public function show($id)
    {
        // Find position with related users and their divisions
        $position = Position::with('users', 'users.division')->find($id);

        // Return error if position not found
        if (!$position) {
            return response()->json([
                'success' => false,
                'message' => 'Position not found'
            ], 404);
        }

        // Return position data as JSON
        return response()->json([
            'success' => true,
            'data' => $position
        ]);
    }

    /**
     * Update the specified position.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     *
     * This method validates and updates an existing position record.
     */
    public function update(Request $request, $id)
    {
        // Find the position to update
        $position = Position::find($id);

        // Return error if position not found
        if (!$position) {
            return response()->json([
                'success' => false,
                'message' => 'Position not found'
            ], 404);
        }

        // Validate the request data for updating a position
        $validator = Validator::make($request->all(), [
            'pos_code' => [
                'required', 'string', 'max:10',
                Rule::unique('login_positions', 'pos_code')->ignore($id, 'pos_id')
            ],
            'pos_name' => 'required|string|max:100',
            'pos_is_active' => 'nullable|boolean',
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
        $validated['pos_updated_by'] = auth()->id() ?? 'system';

        // Update the position
        $position->update($validated);

        // Return success response with updated position
        return response()->json([
            'success' => true,
            'message' => 'Position updated successfully',
            'data' => $position
        ]);
    }

    /**
     * Remove the specified position.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     *
     * This method deletes a position if it exists and has no associated users.
     */
    public function destroy($id)
    {
        // Find the position to delete
        $position = Position::find($id);

        // Return error if position not found
        if (!$position) {
            return response()->json([
                'success' => false,
                'message' => 'Position not found'
            ], 404);
        }

        // Prevent deletion if position has users
        if ($position->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete position. It has associated users.'
            ], 400);
        }

        // Delete the position
        $position->delete();

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Position deleted successfully'
        ]);
    }
}
