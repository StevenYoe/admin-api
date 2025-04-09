<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    /**
     * Check API health
     *
     * @return \Illuminate\Http\Response
     */
    public function check()
    {
        $dbStatus = 'OK';
        
        try {
            // Check database connection
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $dbStatus = 'ERROR: ' . $e->getMessage();
        }

        return response()->json([
            'status' => 'UP',
            'timestamp' => now()->toIso8601String(),
            'services' => [
                'database' => $dbStatus,
                'app' => 'OK'
            ],
            'version' => config('app.version', '1.0.0')
        ]);
    }
}
