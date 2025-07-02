<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Division;
use App\Models\Position;

// DashboardController provides API endpoints for dashboard statistics and chart data.
class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     *
     * @return \Illuminate\Http\Response
     *
     * This method gathers various statistics for the dashboard, such as total and active users,
     * divisions, positions, roles, manager count, and recent users. It also includes chart data
     * for users by division and position.
     */
    public function getStatistics()
    {
        // Collect statistics for dashboard display
        $statistics = [
            'total_users' => User::count(), // Total number of users
            'active_users' => User::where('u_is_active', true)->count(), // Number of active users
            'inactive_users' => User::where('u_is_active', false)->count(), // Number of inactive users
            'total_divisions' => Division::count(), // Total number of divisions
            'active_divisions' => Division::where('div_is_active', true)->count(), // Number of active divisions
            'total_positions' => Position::count(), // Total number of positions
            'active_positions' => Position::where('pos_is_active', true)->count(), // Number of active positions
            'total_roles' => Role::count(), // Total number of roles
            'active_roles' => Role::where('role_is_active', true)->count(), // Number of active roles
            'managers_count' => User::where('u_is_manager', true)->count(), // Number of users with manager status
            'users_by_division' => $this->getUsersByDivision(), // Chart data: users grouped by division
            'users_by_position' => $this->getUsersByPosition(), // Chart data: users grouped by position
            'recent_users' => User::with(['division', 'position'])
                ->orderBy('u_created_at', 'desc')
                ->take(5)
                ->get() // List of 5 most recently created users with their division and position
        ];

        // Return the statistics as a JSON response
        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }

    /**
     * Get users grouped by division for chart
     *
     * @return array
     *
     * This private method returns an array of divisions with the count of users in each division.
     * Useful for dashboard charts.
     */
    private function getUsersByDivision()
    {
        // Get all divisions with the count of related users
        $divisions = Division::withCount('users')->get();
        
        $data = [];
        // Build an array with division name and user count
        foreach ($divisions as $division) {
            $data[] = [
                'name' => $division->div_name,
                'count' => $division->users_count
            ];
        }

        return $data;
    }

    /**
     * Get users grouped by position for chart
     *
     * @return array
     *
     * This private method returns an array of positions with the count of users in each position.
     * Useful for dashboard charts.
     */
    private function getUsersByPosition()
    {
        // Get all positions with the count of related users
        $positions = Position::withCount('users')->get();
        
        $data = [];
        // Build an array with position name and user count
        foreach ($positions as $position) {
            $data[] = [
                'name' => $position->pos_name,
                'count' => $position->users_count
            ];
        }

        return $data;
    }
}
