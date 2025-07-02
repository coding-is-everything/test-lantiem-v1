<?php

namespace App\Http\Controllers\API;

use App\Models\ActivityBreakdown;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ActivityBreakdownController extends Controller
{
    /**
     * Display a listing of the activity breakdown records.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = ActivityBreakdown::query();
            
            // Filter by date range if provided
            if ($request->has(['start_date', 'end_date'])) {
                $query->whereBetween('date', [
                    $request->start_date,
                    $request->end_date
                ]);
            }
            
            // Filter by activity type if provided
            if ($request->has('activity_type')) {
                $query->where('activity_type', $request->activity_type);
            }
            
            // Paginate the results
            $activities = $query->orderBy('date', 'desc')
                              ->paginate(10);
            
            return response()->json([
                'success' => true,
                'data' => $activities
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve activity breakdown data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get activity breakdown data for charts
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function chartData(Request $request)
    {
        try {
            $query = ActivityBreakdown::query();
            
            // Filter by date range if provided
            if ($request->has(['start_date', 'end_date'])) {
                $query->whereBetween('date', [
                    $request->start_date,
                    $request->end_date
                ]);
            }
            
            // Group by activity type and calculate totals
            $activityData = $query->selectRaw('activity_type, SUM(hours) as total_hours, AVG(percentage) as avg_percentage')
                                ->groupBy('activity_type')
                                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $activityData
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve activity breakdown chart data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get activity breakdown by date
     * 
     * @param  string  $date
     * @return \Illuminate\Http\JsonResponse
     */
    public function byDate($date)
    {
        try {
            // Validate date format
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The given data was invalid.',
                    'errors' => [
                        'date' => ['The date must be a valid date in YYYY-MM-DD format.']
                    ]
                ], 422);
            }
            
            $activities = ActivityBreakdown::where('date', $date)
                                        ->orderBy('percentage', 'desc')
                                        ->get();
            
            return response()->json([
                'success' => true,
                'data' => $activities
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve activity breakdown for the specified date',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
