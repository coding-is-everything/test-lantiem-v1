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
            $validator = Validator::make($request->all(), [
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $startDate = $request->start_date;
            $endDate = $request->end_date;
            
            // Calculate the difference in days
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
            $interval = $start->diff($end);
            $daysDifference = (int)$interval->format('%a');
            
            $query = Distance::query()
                ->whereBetween('date', [$startDate, $endDate])
                ->orderBy('date');
            
            // Group by day, week, or month based on the date range
            if ($daysDifference <= 1) {
                // For 1 day or less, group by hour
                $query->selectRaw('HOUR(date) as time_period, SUM(value) as total_distance')
                      ->groupBy('time_period')
                      ->orderBy('time_period');
                
                $distanceData = $query->get()->map(function($item) use ($startDate) {
                    return [
                        'date' => (new \DateTime($startDate))->setTime($item->time_period, 0, 0)->format('Y-m-d H:i:s'),
                        'value' => (float)$item->total_distance
                    ];
                });
            } elseif ($daysDifference <= 7) {
                // For up to a week, group by day
                $query->selectRaw('DATE(date) as date, SUM(value) as total_distance')
                      ->groupBy('date')
                      ->orderBy('date');
                
                $distanceData = $query->get()->map(function($item) {
                    return [
                        'date' => $item->date,
                        'value' => (float)$item->total_distance
                    ];
                });
            } elseif ($daysDifference <= 30) {
                // For up to a month, group by day
                $query->selectRaw('DATE(date) as date, SUM(value) as total_distance')
                      ->groupBy('date')
                      ->orderBy('date');
                
                $distanceData = $query->get()->map(function($item) {
                    return [
                        'date' => $item->date,
                        'value' => (float)$item->total_distance
                    ];
                });
            } else {
                // For longer periods, group by week or month
                if ($daysDifference <= 90) {
                    // For up to 3 months, group by week
                    $query->selectRaw('YEAR(date) as year, WEEK(date, 1) as week, SUM(value) as total_distance')
                          ->groupBy('year', 'week')
                          ->orderBy('year')
                          ->orderBy('week');
                    
                    $distanceData = $query->get()->map(function($item) {
                        $date = new \DateTime();
                        $date->setISODate($item->year, $item->week);
                        return [
                            'date' => $date->format('Y-m-d'),
                            'value' => (float)$item->total_distance
                        ];
                    });
                } else {
                    // For more than 3 months, group by month
                    $query->selectRaw('YEAR(date) as year, MONTH(date) as month, SUM(value) as total_distance')
                          ->groupBy('year', 'month')
                          ->orderBy('year')
                          ->orderBy('month');
                    
                    $distanceData = $query->get()->map(function($item) {
                        return [
                            'date' => "{$item->year}-" . str_pad($item->month, 2, '0', STR_PAD_LEFT) . '-01',
                            'value' => (float)$item->total_distance
                        ];
                    });
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => $distanceData
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error in DistanceController@chartData: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve distance chart data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
