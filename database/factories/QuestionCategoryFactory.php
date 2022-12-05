<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionCategoryFactory extends Factory
{
    public function definition()
    {
        return [
            'title' => fake()->name(),
            'order' => fake()->unique()->randomNumber(),
            'status' => fake()->randomElement(['requested','published']),
        ];
    }
}
