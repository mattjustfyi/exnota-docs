<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Document::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'path' => $this->faker->filePath(),
            'content' => $this->faker->paragraphs(3, true),
            'source_id' => Source::factory(),
        ];
    }
}
