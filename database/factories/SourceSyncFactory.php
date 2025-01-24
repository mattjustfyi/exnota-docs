<?php

namespace Database\Factories;

use App\Models\Source;
use App\Models\SourceSync;
use Illuminate\Database\Eloquent\Factories\Factory;

class SourceSyncFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SourceSync::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'file_paths' => '[]',
            'document_ids' => '[]',
            'source_id' => Source::factory(),
        ];
    }
}
