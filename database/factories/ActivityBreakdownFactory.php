<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ActivityBreakdownFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \App\Models\ActivityBreakdown::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $activityTypes = ['Driving', 'Idle', 'Working', 'Off', 'Maintenance'];
        
        return [
            'date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'activity_type' => $this->faker->randomElement($activityTypes),
            'hours' => $this->faker->randomFloat(2, 0, 24),
            'percentage' => $this->faker->randomFloat(2, 0, 100),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
