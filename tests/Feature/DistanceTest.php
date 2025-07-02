<?php

namespace Tests\Feature;

use App\Models\Distance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DistanceTest extends TestCase
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
     * Test getting paginated distance records
     */
    public function it_returns_paginated_distance_records()
    {
        // Create test data
        Distance::factory()->count(15)->create();

        $response = $this->getJson('/api/distance');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'current_page',
                         'data' => [
                             '*' => ['id', 'date', 'value', 'created_at', 'updated_at']
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
     * Test filtering distance records by date range
     */
    public function it_filters_distance_records_by_date_range()
    {
        // Create test data with specific dates
        $startDate = '2023-01-01';
        $endDate = '2023-01-31';
        
        // Create records within date range
        Distance::factory()->create(['date' => '2023-01-15', 'value' => 100]);
        Distance::factory()->create(['date' => '2023-01-20', 'value' => 150]);
        
        // Create records outside date range
        Distance::factory()->create(['date' => '2022-12-31', 'value' => 200]);
        Distance::factory()->create(['date' => '2023-02-01', 'value' => 250]);

        $response = $this->getJson("/api/distance?start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data.data')
                 ->assertJsonFragment(['value' => 100])
                 ->assertJsonFragment(['value' => 150])
                 ->assertJsonMissing(['value' => 200])
                 ->assertJsonMissing(['value' => 250]);
    }

    /**
     * @test
     * Test getting chart data for distances
     */
    public function it_returns_chart_data_for_distances()
    {
        // Clear any existing data to avoid conflicts
        Distance::truncate();
        
        // Create test data with specific dates and values
        $testData1 = ['date' => '2023-01-01', 'value' => 100];
        $testData2 = ['date' => '2023-01-02', 'value' => 150];
        
        $distance1 = Distance::factory()->create($testData1);
        $distance2 = Distance::factory()->create($testData2);

        $response = $this->getJson('/api/distance/chart-data');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);
        
        // Get the response data
        $responseData = $response->json('data');
        
        // Convert test data to the expected format in the response
        $expectedData1 = [
            'date' => $testData1['date'] . 'T00:00:00.000000Z',
            'value' => $testData1['value']
        ];
        
        $expectedData2 = [
            'date' => $testData2['date'] . 'T00:00:00.000000Z',
            'value' => $testData2['value']
        ];
        
        // Check if the response contains our test data
        $this->assertContainsEquals($expectedData1, $responseData);
        $this->assertContainsEquals($expectedData2, $responseData);
    }

    /**
     * @test
     * Test validation for invalid date format
     */
    public function it_validates_date_format()
    {
        $response = $this->getJson('/api/distance?start_date=invalid-date&end_date=2023-01-31');

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['start_date']);
    }
}
