<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Division;
use App\Models\Position;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     *
     * @return \Illuminate\Http\Response
     */
    public function getStatistics()
    {
        $statistics = [
            'total_users' => User::count(),
            'active_users' => User::where('u_is_active', true)->count(),
            'inactive_users' => User::where('u_is_active', false)->count(),
            'total_divisions' => Division::count(),
            'active_divisions' => Division::where('div_is_active', true)->count(),
            'total_positions' => Position::count(),
            'active_positions' => Position::where('pos_is_active', true)->count(),
            'total_roles' => Role::count(),
            'active_roles' => Role::where('role_is_active', true)->count(),
            'managers_count' => User::where('u_is_manager', true)->count(),
            'users_by_division' => $this->getUsersByDivision(),
            'recent_users' => User::with(['division', 'position'])
                ->orderBy('u_created_at', 'desc')
                ->take(5)
                ->get()
        ];

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }

    /**
     * Get users grouped by division for chart
     *
     * @return array
     */
    private function getUsersByDivision()
    {
        $divisions = Division::withCount('users')->get();
        
        $data = [];
        foreach ($divisions as $division) {
            $data[] = [
                'name' => $division->div_name,
                'count' => $division->users_count
            ];
        }

        return $data;
    }
}
