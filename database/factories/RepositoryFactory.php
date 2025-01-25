<?php

namespace Database\Factories;

use App\Models\Repository;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Repository> */
class RepositoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Repository>
     */
    protected $model = Repository::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'url' => $this->faker->url(),
            'token' => $this->faker->word(),
        ];
    }
}
