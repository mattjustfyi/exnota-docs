<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Document;
use App\Models\NotionWorkspace;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Shared
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

        // Source: OneLevel test data
        $source = $repository->sources()->create([
            'path' => '/tests/Data/Docs/OneLevel',
            'notion_database_id' => $database->id,
        ]);

        $paths = $this->localFilePaths('tests/Data/Docs/OneLevel');

        foreach ($paths as $path) {
            Document::factory()
                ->contentfromfile($path['absolute'])
                ->create([
                    'source_id' => $source->id,
                    'path' => $path['relative'],
                ]);
        }

        // Source: ThreeLevel test data
        $source = $repository->sources()->create([
            'path' => '/tests/Data/Docs/ThreeLevel',
            'notion_database_id' => $database->id,
        ]);

        $paths = $this->localFilePaths('tests/Data/Docs/ThreeLevel');

        foreach ($paths as $path) {
            Document::factory()
                ->contentfromfile($path['absolute'])
                ->create([
                    'source_id' => $source->id,
                    'path' => $path['relative'],
                ]);
        }

    }

    /**
     * @return array<int, array{relative: string, absolute: string}>
     */
    private function localFilePaths(string $path): array
    {
        $sourcePath = base_path($path);
        $files = File::allFiles($sourcePath);

        $projectRelativePath = base_path();

        /** @var array<int, array{absolute: string, relative: string}>  $paths */
        $paths = array_map(fn ($file) => [
            'absolute' => $file->getPathname(),
            'relative' => str_replace($projectRelativePath, '', $file->getPathname()),
        ], $files);

        return $paths;
    }
}
