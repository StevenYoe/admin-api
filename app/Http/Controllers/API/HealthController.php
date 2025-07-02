<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

// HealthController provides an endpoint to check the health status of the API and its services.
class HealthController extends Controller
{
    /**
     * Check API health
     *
     * @return \Illuminate\Http\Response
     *
     * This method checks the application's health by verifying the database connection
     * and returns a JSON response with the status, timestamp, and version information.
     */
    public function check()
    {
        $dbStatus = 'OK';
        
        try {
            // Attempt to connect to the database
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            // If connection fails, set database status to error message
            $dbStatus = 'ERROR: ' . $e->getMessage();
        }

        // Return health status as JSON, including database and app status, timestamp, and version
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
