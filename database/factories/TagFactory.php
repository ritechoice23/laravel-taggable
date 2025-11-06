<?php

namespace Ritechoice23\Taggable\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Ritechoice23\Taggable\Models\Tag;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'meta' => [
                'color' => $this->faker->hexColor(),
                'description' => $this->faker->sentence(),
            ],
            'usage_count' => $this->faker->numberBetween(0, 100),
            'trending_score' => $this->faker->randomFloat(2, 0, 100),
        ];
    }
}
