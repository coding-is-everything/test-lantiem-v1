<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Faker\Factory as Faker;

class DashboardDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        // Clear existing data
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('distance')->truncate();
        DB::table('engine_hours')->truncate();
        DB::table('activity_breakdown')->truncate();
        DB::table('messages_received')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Activity types and message types
        $activityTypes = ['Driving', 'Idle', 'Working', 'Off', 'Maintenance'];
        $messageTypes = ['alert', 'notification'];
        
        // Generate data for the last 100 days
        $now = Carbon::now();
        
        // Generate 100 records for each table
        for ($i = 0; $i < 100; $i++) {
            $date = $now->copy()->subDays(99 - $i);
            
            // 1. Distance data - matches migration: date, value
            DB::table('distance')->insert([
                'date' => $date->format('Y-m-d'),
                'value' => $faker->numberBetween(50, 500), // km
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2. Engine hours data - matches migration: date, hours
            DB::table('engine_hours')->insert([
                'date' => $date->format('Y-m-d'),
                'hours' => $faker->randomFloat(2, 0.5, 12.0), // hours (2 decimal places)
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // 3. Activity breakdown data
            $totalPercentage = 0;
            $activityCount = count($activityTypes);
            
            // Generate 5 activity records per day
            for ($j = 0; $j < $activityCount; $j++) {
                $percentage = $j === $activityCount - 1 
                    ? 100 - $totalPercentage 
                    : rand(5, min(100 - $totalPercentage - ($activityCount - $j - 1) * 5, 70));
                
                $totalPercentage += $percentage;
                
                DB::table('activity_breakdown')->insert([
                    'date' => $date->format('Y-m-d'),
                    'activity_type' => $activityTypes[$j],
                    'hours' => round(($percentage / 100) * 24, 2),
                    'percentage' => $percentage,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 4. Messages received data - matches migration: date, count, message_type, details
            $messageCount = $faker->numberBetween(5, 20);
            $messageType = $faker->randomElement(['Alert', 'Warning', 'Info', 'Error', 'Status']);
            
            DB::table('messages_received')->insert([
                'date' => $date->format('Y-m-d'),
                'count' => $messageCount,
                'message_type' => $messageType,
                'details' => "$messageCount $messageType messages received on " . $date->format('Y-m-d'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
