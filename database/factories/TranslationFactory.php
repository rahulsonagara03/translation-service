<?php

namespace Database\Factories;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Translation>
 */
class TranslationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Translation>
     */
    protected $model = Translation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, string>
     */
    public function definition(): array
    {
        return [
            'locale' => $this->faker->randomElement(['en', 'fr', 'es']),
            'group' => $this->faker->randomElement(['auth', 'common', 'dashboard']),
            'key' => $this->faker->unique()->slug(3, '.'),
            'value' => $this->faker->sentence(),
        ];
    }
}