<?php

namespace Tests\Feature;

use App\Models\ActivityBreakdown;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ActivityBreakdownTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create and authenticate a user for testing
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);
    }

    /**
     * @test
     * Test getting paginated activity breakdown records
     */
    public function it_returns_paginated_activity_breakdown_records()
    {
        // Create test data
        ActivityBreakdown::factory()->count(15)->create();

        $response = $this->getJson('/api/activity-breakdown');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'current_page',
                         'data' => [
                             '*' => [
                                 'id', 
                                 'activity_type', 
                                 'hours', 
                                 'percentage', 
                                 'date', 
                                 'created_at', 
                                 'updated_at'
                             ]
                         ],
                         'first_page_url',
                         'from',
                         'last_page',
                         'last_page_url',
                         'links',
                         'next_page_url',
                         'path',
                         'per_page',
                         'prev_page_url',
                         'to',
                         'total'
                     ]
                 ]);
    }

    /**
     * @test
     * Test filtering activity breakdown by activity type
     */
    public function it_filters_activity_breakdown_by_activity_type()
    {
        // Clear any existing data to ensure a clean test environment
        ActivityBreakdown::truncate();
        
        // Create test data with specific activity types
        $drivingActivity = ActivityBreakdown::factory()->create([
            'activity_type' => 'Driving', 
            'hours' => 8.5,
            'date' => '2023-01-01'
        ]);
        
        // Create other activities that should be filtered out
        ActivityBreakdown::factory()->create([
            'activity_type' => 'Idle', 
            'hours' => 3.2,
            'date' => '2023-01-01'
        ]);
        
        ActivityBreakdown::factory()->create([
            'activity_type' => 'Working', 
            'hours' => 5.7,
            'date' => '2023-01-01'
        ]);

        // Make the API request with the activity_type filter
        $response = $this->getJson('/api/activity-breakdown?activity_type=Driving');

        // Verify the response
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'data' => [
                             '*' => ['id', 'activity_type', 'hours', 'percentage', 'date']
                         ]
                     ]
                 ]);
        
        // Get the response data
        $responseData = $response->json('data.data');
        
        // Verify we only got the Driving activity
        $this->assertCount(1, $responseData);
        $this->assertEquals('Driving', $responseData[0]['activity_type']);
        
        // Verify the response contains the expected activity
        $this->assertEquals($drivingActivity->id, $responseData[0]['id']);
    }

    /**
     * @test
     * Test getting activity breakdown by date
     */
    public function it_returns_activity_breakdown_by_date()
    {
        $targetDate = '2023-04-15';
        
        // Create test data for the target date
        ActivityBreakdown::factory()->create([
            'date' => $targetDate,
            'activity_type' => 'Driving',
            'hours' => 8.5,
            'percentage' => 35.42
        ]);
        
        // Create test data for a different date
        ActivityBreakdown::factory()->create([
            'date' => '2023-04-16',
            'activity_type' => 'Idle',
            'hours' => 3.2,
            'percentage' => 13.33
        ]);

        $response = $this->getJson("/api/activity-breakdown/by-date/{$targetDate}");

        $response->assertStatus(200);
        
        // Get the response data
        $responseData = $response->json('data');
        
        // Verify the response structure and data
        $this->assertCount(1, $responseData);
        $this->assertEquals('Driving', $responseData[0]['activity_type']);
        $this->assertEquals(8.5, $responseData[0]['hours']);
        $this->assertEquals(35.42, $responseData[0]['percentage']);
        
        // Verify the date is in the correct format (YYYY-MM-DD)
        $this->assertStringStartsWith($targetDate, $responseData[0]['date']);
    }

    /**
     * @test
     * Test getting chart data for activity breakdown
     */
    public function it_returns_chart_data_for_activity_breakdown()
    {
        // Create test data
        ActivityBreakdown::factory()->create([
            'activity_type' => 'Driving',
            'hours' => 8.5,
            'percentage' => 35.42
        ]);
        
        ActivityBreakdown::factory()->create([
            'activity_type' => 'Idle',
            'hours' => 3.2,
            'percentage' => 13.33
        ]);

        $response = $this->getJson('/api/activity-breakdown/chart-data');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => [
                             'activity_type',
                             'total_hours',
                             'avg_percentage'
                         ]
                     ]
                 ]);
    }

    /**
     * @test
     * Test validation for invalid date format in by-date endpoint
     */
    public function it_validates_date_format_in_by_date_endpoint()
    {
        $response = $this->getJson('/api/activity-breakdown/by-date/invalid-date');

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['date']);
    }
}
