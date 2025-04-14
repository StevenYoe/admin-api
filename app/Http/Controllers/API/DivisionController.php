<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Division;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DivisionController extends Controller
{
    /**
     * Display a listing of divisions with pagination.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        $sortBy = $request->query('sort_by', 'div_id');
        $sortOrder = $request->query('sort_order', 'asc');

        $allowedSortColumns = ['div_id', 'div_code', 'div_name'];
        $sortBy = in_array($sortBy, $allowedSortColumns) ? $sortBy : 'div_id';

        $divisions = Division::orderBy($sortBy, $sortOrder)
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $divisions,
        ]);
    }

    /**
     * Get all active divisions without pagination.
     *
     * @return \Illuminate\Http\Response
     */
    public function all()
    {
        $divisions = Division::where('div_is_active', true)->get();

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
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'div_code' => 'required|string|max:10|unique:login.divisions,div_code',
            'div_name' => 'required|string|max:100',
            'div_is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();
        $validated['div_created_by'] = auth()->id() ?? 'system';
        $validated['div_updated_by'] = auth()->id() ?? 'system';

        $division = Division::create($validated);

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
     */
    public function show($id)
    {
        $division = Division::with('users', 'users.position')->find($id);

        if (!$division) {
            return response()->json([
                'success' => false,
                'message' => 'Division not found'
            ], 404);
        }

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
     */
    public function update(Request $request, $id)
    {
        $division = Division::find($id);

        if (!$division) {
            return response()->json([
                'success' => false,
                'message' => 'Division not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'div_code' => [
                'required', 'string', 'max:10',
                Rule::unique('login.divisions', 'div_code')->ignore($id, 'div_id')
            ],
            'div_name' => 'required|string|max:100',
            'div_is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();
        $validated['div_updated_by'] = auth()->id() ?? 'system';

        $division->update($validated);

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
     */
    public function destroy($id)
    {
        $division = Division::find($id);

        if (!$division) {
            return response()->json([
                'success' => false,
                'message' => 'Division not found'
            ], 404);
        }

        // Check if there are users in this division
        if ($division->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete division. It has associated users.'
            ], 400);
        }

        $division->delete();

        return response()->json([
            'success' => true,
            'message' => 'Division deleted successfully'
        ]);
    }
}
