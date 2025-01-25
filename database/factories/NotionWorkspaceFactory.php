<?php

namespace Database\Factories;

use App\Models\NotionWorkspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<NotionWorkspace> */
class NotionWorkspaceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<NotionWorkspace>
     */
    protected $model = NotionWorkspace::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'token' => $this->faker->word(),
        ];
    }
}
