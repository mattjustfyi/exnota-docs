<?php

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

        /** @noinspection LaravelFunctionsInspection */
        $workspace = NotionWorkspace::factory()->create([
            'name' => 'Test Workspace',
            'token' => env('NOTION_AUTH_TOKEN'),
        ]);

        /** @noinspection LaravelFunctionsInspection */
        $database = $workspace->notionDatabases()->create([
            'name' => 'Test Database',
            'notion_id' => env('NOTION_TEST_DATABASE_ID'),
        ]);

        /** @noinspection LaravelFunctionsInspection */
        $repository = Repository::factory()->create([
            'url' => 'https://api.github.com/repos/mttschltz/exnota-docs/contents',
            'token' => env('NOTION_AUTH_TOKEN'),
        ]);

        $source = $repository->sources()->create([
            'path' => '/tests/Data/Docs/OneLevel',
            'notion_database_id' => $database->id,
        ]);

        $source->documents()->save(Document::factory()->make());
    }
}
