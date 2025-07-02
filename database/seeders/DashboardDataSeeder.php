<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        DB::table('distance')->truncate();
        DB::table('engine_hours')->truncate();
        DB::table('activity_breakdown')->truncate();
        DB::table('messages_received')->truncate();

        // Generate data for the last 50 days
        $now = Carbon::now();
        $activityTypes = ['Driving', 'Idle', 'Working', 'Off', 'Maintenance'];
        $messageTypes = ['Alert', 'Warning', 'Info', 'Error', 'Status'];

        for ($i = 0; $i < 50; $i++) {
            $date = $now->copy()->subDays(49 - $i);
            
            // Distance data
            DB::table('distance')->insert([
                'date' => $date->format('Y-m-d'),
                'value' => rand(50, 500), // Random distance between 50-500
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Engine hours data
            DB::table('engine_hours')->insert([
                'date' => $date->format('Y-m-d'),
                'hours' => round(rand(500, 1200) / 10, 1), // Random hours between 50.0-120.0
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Activity breakdown data
            $totalPercentage = 0;
            $activityCount = count($activityTypes);
            
            for ($j = 0; $j < $activityCount; $j++) {
                $percentage = $j === $activityCount - 1 
                    ? 100 - $totalPercentage 
                    : rand(5, min(50, 100 - $totalPercentage - (($activityCount - $j - 1) * 5)));
                
                $totalPercentage += $percentage;
                
                DB::table('activity_breakdown')->insert([
                    'activity_type' => $activityTypes[$j],
                    'hours' => round(($percentage / 100) * 24, 2), // Convert percentage to hours in a day
                    'percentage' => $percentage,
                    'date' => $date->format('Y-m-d'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Messages received data
            $messageCount = rand(5, 50);
            DB::table('messages_received')->insert([
                'date' => $date->format('Y-m-d'),
                'count' => $messageCount,
                'message_type' => $messageTypes[array_rand($messageTypes)],
                'details' => "Generated $messageCount messages for " . $date->format('Y-m-d'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
