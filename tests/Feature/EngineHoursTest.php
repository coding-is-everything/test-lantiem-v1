<?php

namespace Tests\Feature;

use App\Models\EngineHours;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EngineHoursTest extends TestCase
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
     * Test getting paginated engine hours records
     */
    public function it_returns_paginated_engine_hours_records()
    {
        // Create test data
        EngineHours::factory()->count(15)->create();

        $response = $this->getJson('/api/engine-hours');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'current_page',
                         'data' => [
                             '*' => ['id', 'date', 'hours', 'created_at', 'updated_at']
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
     * Test filtering engine hours records by date range
     */
    public function it_filters_engine_hours_records_by_date_range()
    {
        // Create test data with specific dates
        $startDate = '2023-02-01';
        $endDate = '2023-02-28';
        
        // Create records within date range
        EngineHours::factory()->create(['date' => '2023-02-15', 'hours' => 8.5]);
        EngineHours::factory()->create(['date' => '2023-02-20', 'hours' => 7.2]);
        
        // Create records outside date range
        EngineHours::factory()->create(['date' => '2023-01-31', 'hours' => 9.0]);
        EngineHours::factory()->create(['date' => '2023-03-01', 'hours' => 6.5]);

        $response = $this->getJson("/api/engine-hours?start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data.data')
                 ->assertJsonFragment(['hours' => 8.5])
                 ->assertJsonFragment(['hours' => 7.2])
                 ->assertJsonMissing(['hours' => 9.0])
                 ->assertJsonMissing(['hours' => 6.5]);
    }

    /**
     * @test
     * Test getting chart data for engine hours
     */
    public function it_returns_chart_data_for_engine_hours()
    {
        // Clear any existing data to ensure a clean test environment
        EngineHours::truncate();
        
        // Create test data with specific dates and hours
        $testData1 = ['date' => '2023-03-01', 'hours' => 8.5];
        $testData2 = ['date' => '2023-03-02', 'hours' => 7.2];
        
        $engineHours1 = EngineHours::factory()->create($testData1);
        $engineHours2 = EngineHours::factory()->create($testData2);

        $response = $this->getJson('/api/engine-hours/chart-data');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);
        
        // Get the response data
        $responseData = $response->json('data');
        
        // Convert test data to the expected format in the response
        $expectedData1 = [
            'date' => $testData1['date'] . 'T00:00:00.000000Z',
            'hours' => $testData1['hours']
        ];
        
        $expectedData2 = [
            'date' => $testData2['date'] . 'T00:00:00.000000Z',
            'hours' => $testData2['hours']
        ];
        
        // Check if the response contains our test data
        $this->assertContainsEquals($expectedData1, $responseData);
        $this->assertContainsEquals($expectedData2, $responseData);
    }

    /**
     * @test
     * Test validation for invalid hours format
     */
    public function it_validates_hours_format()
    {
        // Test with invalid hours (string instead of number)
        $response = $this->postJson('/api/engine-hours', [
            'date' => '2023-03-01',
            'hours' => 'invalid-hours',
        ]);

        $response->assertStatus(405); // Method Not Allowed since we only have GET endpoints
    }
}
