<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->text(32),
            'description' => $this->faker->text(512),
            'type' => Task::TYPE_BASIC,
            'status' => Task::STATUS_TODO,
            'owner_id' => User::factory(),
        ];
    }

    public function advanced(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => Task::TYPE_ADVANCED,
            ];
        });
    }

    public function expert(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => Task::TYPE_EXPERT,
            ];
        });
    }

    public function closed(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => Task::STATUS_CLOSED,
            ];
        });
    }

    public function hold(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => Task::STATUS_HOLD,
            ];
        });
    }
}
