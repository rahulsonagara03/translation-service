<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Tag>
     */
    protected $model = Tag::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, string>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->bothify('tag-####-????'),
        ];
    }
}