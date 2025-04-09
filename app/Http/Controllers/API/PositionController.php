<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PositionController extends Controller
{
    /**
     * Display a listing of positions with pagination.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        $positions = Position::paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $positions,
        ]);
    }

    /**
     * Get all active positions without pagination.
     *
     * @return \Illuminate\Http\Response
     */
    public function all()
    {
        $positions = Position::where('pos_is_active', true)->get();

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
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pos_code' => 'required|string|max:10|unique:login.positions,pos_code',
            'pos_name' => 'required|string|max:100',
            'pos_is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();
        $validated['pos_created_by'] = auth()->id() ?? 'system';
        $validated['pos_updated_by'] = auth()->id() ?? 'system';

        $position = Position::create($validated);

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
     */
    public function show($id)
    {
        $position = Position::with('users')->find($id);

        if (!$position) {
            return response()->json([
                'success' => false,
                'message' => 'Position not found'
            ], 404);
        }

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
     */
    public function update(Request $request, $id)
    {
        $position = Position::find($id);

        if (!$position) {
            return response()->json([
                'success' => false,
                'message' => 'Position not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'pos_code' => [
                'required', 'string', 'max:10',
                Rule::unique('login.positions', 'pos_code')->ignore($id, 'pos_id')
            ],
            'pos_name' => 'required|string|max:100',
            'pos_is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();
        $validated['pos_updated_by'] = auth()->id() ?? 'system';

        $position->update($validated);

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
     */
    public function destroy($id)
    {
        $position = Position::find($id);

        if (!$position) {
            return response()->json([
                'success' => false,
                'message' => 'Position not found'
            ], 404);
        }

        // Check if there are users in this position
        if ($position->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete position. It has associated users.'
            ], 400);
        }

        $position->delete();

        return response()->json([
            'success' => true,
            'message' => 'Position deleted successfully'
        ]);
    }
}
