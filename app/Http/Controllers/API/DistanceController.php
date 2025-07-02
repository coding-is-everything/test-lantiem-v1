<?php

namespace App\Http\Controllers\API;

use App\Models\Distance;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class DistanceController extends Controller
{
    /**
     * Display a listing of the distance records.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => ['nullable', 'date'],
                'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = Distance::query();
            
            // Filter by date range if provided
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('date', [
                    $request->start_date,
                    $request->end_date
                ]);
            }
            
            // Paginate the results
            $distances = $query->orderBy('date', 'desc')->paginate(10);
            
            return response()->json([
                'success' => true,
                'data' => $distances
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve distance data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get aggregated distance data for charts
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function chartData(Request $request)
    {
        try {
            $query = Distance::query();
            
            // Filter by date range if provided
            if ($request->has(['start_date', 'end_date'])) {
                $query->whereBetween('date', [
                    $request->start_date,
                    $request->end_date
                ]);
            }
            
            $distanceData = $query->orderBy('date')
                                ->get(['date', 'value']);
            
            return response()->json([
                'success' => true,
                'data' => $distanceData
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve distance chart data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
