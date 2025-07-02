<?php

namespace App\Http\Controllers\API;

use App\Models\EngineHours;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class EngineHoursController extends Controller
{
    /**
     * Display a listing of the engine hours records.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = EngineHours::query();
            
            // Filter by date range if provided
            if ($request->has(['start_date', 'end_date'])) {
                $query->whereBetween('date', [
                    $request->start_date,
                    $request->end_date
                ]);
            }
            
            // Paginate the results
            $engineHours = $query->orderBy('date', 'desc')->paginate(10);
            
            return response()->json([
                'success' => true,
                'data' => $engineHours
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve engine hours data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get aggregated engine hours data for charts
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function chartData(Request $request)
    {
        try {
            $query = EngineHours::query();
            
            // Filter by date range if provided
            if ($request->has(['start_date', 'end_date'])) {
                $query->whereBetween('date', [
                    $request->start_date,
                    $request->end_date
                ]);
            }
            
            $engineHoursData = $query->orderBy('date')
                                  ->get(['date', 'hours']);
            
            return response()->json([
                'success' => true,
                'data' => $engineHoursData
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve engine hours chart data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
