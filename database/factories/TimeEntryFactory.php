<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\TimeEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TimeEntry> */
class TimeEntryFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->subHours(3)->startOfMinute();

        return [
            'employee_id' => Employee::factory(),
            'description' => fake()->sentence(4),
            'started_at' => $start,
            'ended_at' => $start->copy()->addHour(),
        ];
    }

    public function running(): static
    {
        return $this->state(['ended_at' => null]);
    }
}
