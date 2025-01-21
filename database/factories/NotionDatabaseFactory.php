<?php

namespace Database\Factories;

use App\Models\NotionDatabase;
use App\Models\NotionWorkspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotionDatabaseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = NotionDatabase::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'notion_workspace_id' => NotionWorkspace::factory(),
        ];
    }
}
