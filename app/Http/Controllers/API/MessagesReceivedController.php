<?php

namespace App\Http\Controllers\API;

use App\Models\MessagesReceived;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class MessagesReceivedController extends Controller
{
    /**
     * Display a listing of the messages received records.
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
                'message_type' => ['nullable', 'string'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = MessagesReceived::query();
            
            // Filter by date range if provided
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('date', [
                    $request->start_date,
                    $request->end_date
                ]);
            }
            
            // Filter by message type if provided
            if ($request->has('message_type')) {
                $query->where('message_type', $request->message_type);
            }
            
            // Paginate the results
            $messages = $query->orderBy('date', 'desc')
                            ->paginate(10);
            
            return response()->json([
                'success' => true,
                'data' => $messages
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve messages data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get messages received data for charts
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function chartData(Request $request)
    {
        try {
            $query = MessagesReceived::query();
            
            // Filter by date range if provided
            if ($request->has(['start_date', 'end_date'])) {
                $query->whereBetween('date', [
                    $request->start_date,
                    $request->end_date
                ]);
            }
            
            // Group by message type and calculate totals
            $messageData = $query->selectRaw('message_type, SUM(count) as total_count')
                               ->groupBy('message_type')
                               ->get();
            
            // Get daily message counts for line chart
            $dailyData = $query->selectRaw('date, SUM(count) as daily_count')
                             ->groupBy('date')
                             ->orderBy('date')
                             ->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'by_type' => $messageData,
                    'daily' => $dailyData
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve messages chart data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get message statistics
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics()
    {
        try {
            $totalMessages = MessagesReceived::sum('count');
            $messageTypes = MessagesReceived::select('message_type')
                                         ->selectRaw('SUM(count) as type_count')
                                         ->groupBy('message_type')
                                         ->orderBy('type_count', 'desc')
                                         ->get();
            
            $latestMessages = MessagesReceived::orderBy('date', 'desc')
                                           ->take(5)
                                           ->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'total_messages' => $totalMessages,
                    'message_types' => $messageTypes,
                    'latest_messages' => $latestMessages
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve message statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
