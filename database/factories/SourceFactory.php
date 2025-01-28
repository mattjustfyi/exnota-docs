<?php

namespace Database\Factories;

use App\Models\NotionDatabase;
use App\Models\Repository;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

class SourceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Source::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'path' => fake()->filePath(),
            'notion_database_id' => NotionDatabase::factory(),
            'repository_id' => Repository::factory(),
        ];
    }
}
