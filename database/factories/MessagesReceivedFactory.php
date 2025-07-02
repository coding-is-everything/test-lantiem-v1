<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class MessagesReceivedFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \App\Models\MessagesReceived::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $messageTypes = ['Alert', 'Warning', 'Info', 'Error', 'Status'];
        $messageType = $this->faker->randomElement($messageTypes);
        
        $details = [
            'Alert' => 'High temperature alert',
            'Warning' => 'Low battery warning',
            'Info' => 'System information update',
            'Error' => 'System error occurred',
            'Status' => 'System status update'
        ];
        
        return [
            'date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'count' => $this->faker->numberBetween(1, 50),
            'message_type' => $messageType,
            'details' => $details[$messageType],
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
