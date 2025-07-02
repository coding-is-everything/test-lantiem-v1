<?php

namespace Tests\Feature;

use App\Models\MessagesReceived;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MessagesReceivedTest extends TestCase
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
     * Test getting paginated messages received records
     */
    public function it_returns_paginated_messages_received_records()
    {
        // Create test data
        MessagesReceived::factory()->count(15)->create();

        $response = $this->getJson('/api/messages-received');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'current_page',
                         'data' => [
                             '*' => [
                                 'id', 
                                 'date', 
                                 'count', 
                                 'message_type',
                                 'details',
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
     * Test filtering messages by message type
     */
    public function it_filters_messages_by_message_type()
    {
        // Clear any existing data to ensure a clean test environment
        MessagesReceived::truncate();
        
        // Create test data with specific message types
        $alertMessage = MessagesReceived::factory()->create([
            'message_type' => 'Alert',
            'count' => 5,
            'details' => 'High temperature alert',
            'date' => '2023-03-01'
        ]);
        
        // Create another alert to test filtering works with multiple alerts
        MessagesReceived::factory()->create([
            'message_type' => 'Alert',
            'count' => 2,
            'details' => 'Door open alert',
            'date' => '2023-03-02'
        ]);
        
        // Create a different message type that should be filtered out
        MessagesReceived::factory()->create([
            'message_type' => 'Warning',
            'count' => 3,
            'details' => 'Low battery warning',
            'date' => '2023-03-01'
        ]);

        // Make the API request with the message_type filter
        $response = $this->getJson('/api/messages-received?message_type=Alert');

        // Get the response data
        $responseData = $response->json('data.data');
        
        // Verify the response
        $response->assertStatus(200);
        
        // Check we only got Alert messages
        $this->assertCount(2, $responseData);
        $this->assertContains('Alert', array_column($responseData, 'message_type'));
        $this->assertNotContains('Warning', array_column($responseData, 'message_type'));
        
        // Verify the response contains our test alert
        $this->assertContains($alertMessage->id, array_column($responseData, 'id'));
    }

    /**
     * @test
     * Test getting messages statistics
     */
    public function it_returns_messages_statistics()
    {
        // Create test data
        MessagesReceived::factory()->create(['message_type' => 'Alert', 'count' => 5]);
        MessagesReceived::factory()->create(['message_type' => 'Alert', 'count' => 3]);
        MessagesReceived::factory()->create(['message_type' => 'Warning', 'count' => 2]);

        $response = $this->getJson('/api/messages-received/statistics');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'total_messages',
                         'message_types' => [
                             '*' => [
                                 'message_type',
                                 'type_count'
                             ]
                         ],
                         'latest_messages' => [
                             '*' => [
                                 'id',
                                 'date',
                                 'count',
                                 'message_type',
                                 'details'
                             ]
                         ]
                     ]
                 ]);
    }

    /**
     * @test
     * Test getting chart data for messages received
     */
    public function it_returns_chart_data_for_messages_received()
    {
        // Create test data
        $message1 = MessagesReceived::factory()->create([
            'date' => '2023-05-01',
            'message_type' => 'Alert',
            'count' => 5
        ]);
        
        $message2 = MessagesReceived::factory()->create([
            'date' => '2023-05-02',
            'message_type' => 'Warning',
            'count' => 3
        ]);

        $response = $this->getJson('/api/messages-received/chart-data');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'by_type' => [
                             '*' => [
                                 'message_type',
                                 'total_count'
                             ]
                         ],
                         'daily' => [
                             '*' => [
                                 'date',
                                 'daily_count'
                             ]
                         ]
                     ]
                 ]);
    }

    /**
     * @test
     * Test validation for invalid date range
     */
    public function it_validates_date_range()
    {
        $response = $this->getJson('/api/messages-received?start_date=2023-01-01&end_date=invalid-date');

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['end_date']);
    }
}
