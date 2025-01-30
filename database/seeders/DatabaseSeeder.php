<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Document;
use App\Models\NotionWorkspace;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Matt',
            'email' => 'email@matt.fyi',
            'password' => Hash::make('password'),
        ]);

        $workspace = NotionWorkspace::factory()->createOne([
            'name' => 'Test Workspace',
            'token' => config('services.notion.token'),
        ]);

        $database = $workspace->notionDatabases()->create([
            'name' => 'Test Database',
            'notion_id' => config('services.notion.test_database_id'),
            'notion_home_id' => config('services.notion.test_home_id'),
        ]);

        $repository = Repository::factory()->create([
            'url' => 'https://api.github.com/repos/mttschltz/exnota-docs/contents',
            'token' => config('services.github.token'),
        ]);

        $source = $repository->sources()->create([
            'path' => '/tests/Data/Docs/OneLevel',
            'notion_database_id' => $database->id,
        ]);
        Document::factory()->create([
            'source_id' => $source->id,
        ]);
    }
}
