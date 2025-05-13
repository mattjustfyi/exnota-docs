<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Document;
use App\Models\NotionDatabase;
use App\Models\NotionWorkspace;
use App\Models\Repository;
use App\Models\Source;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Webmozart\Assert\Assert;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Shared
        $user = User::factory()->create([
            'name' => 'Matt',
            'email' => 'email@matt.fyi',
            'password' => Hash::make('password'),
            'github_id' => 'test-github-id',
            'github_token' => 'test-github-token',
            'github_refresh_token' => 'test-github-refresh-token',
        ]);

        $workspace = NotionWorkspace::factory()->createOne([
            'name' => 'Test Workspace',
            'token' => config('services.notion.token'),
        ]);
        Assert::isInstanceOf($workspace, NotionWorkspace::class);

        $database = $workspace->notionDatabases()->create([
            'name' => 'Test Database',
            'notion_id' => config('services.notion.test_database_id'),
            'notion_home_id' => config('services.notion.test_home_id'),
        ]);
        Assert::isInstanceOf($database, NotionDatabase::class);

        $repository = Repository::factory()->create([
            'url' => 'https://api.github.com/repos/mttschltz/exnota-docs/contents',
            // TODO: Not sure if this should be required. It certainly shouldn't be
            // from config.
            'token' => config('services.github.token') ?? 'dummy value',
        ]);
        Assert::isInstanceOf($repository, Repository::class);

        // Source: OneLevel test data
        $source = $repository->sources()->create([
            'path' => 'tests/Data/Docs/OneLevel',
            'notion_database_id' => $database->id,
        ]);
        Assert::isInstanceOf($source, Source::class);

        $paths = $this->localFilePaths('tests/Data/Docs/OneLevel');

        foreach ($paths as $path) {
            // Paths from GitHub come without a forward slash, so remove to
            // normalize
            $pathWithoutFirstSlash = preg_replace('/^\//', '', $path['relative']);
            Document::factory()
                ->contentfromfile($path['absolute'])
                ->create([
                    'source_id' => $source->id,
                    // Prepend `/` to match the format from GitHub
                    'path' => $pathWithoutFirstSlash,
                ]);
        }

        // Source: ThreeLevel test data
        $source = $repository->sources()->create([
            'path' => 'tests/Data/Docs/ThreeLevel',
            'notion_database_id' => $database->id,
        ]);
        Assert::isInstanceOf($source, Source::class);

        $paths = $this->localFilePaths('tests/Data/Docs/ThreeLevel');

        foreach ($paths as $path) {
            // Paths from GitHub come without a forward slash, so remove to
            // normalize
            $pathWithoutFirstSlash = preg_replace('/^\//', '', $path['relative']);
            Document::factory()
                ->contentfromfile($path['absolute'])
                ->create([
                    'source_id' => $source->id,
                    'path' => $pathWithoutFirstSlash,
                ]);
        }
    }

    /**
     * @return array<int, array{relative: string, absolute: string}>
     */
    private function localFilePaths(string $sourcePath): array
    {
        $absoluteSourcePath = base_path($sourcePath);
        $files = File::allFiles($absoluteSourcePath);

        $projectRelativePath = base_path();

        /** @var array<int, array{absolute: string, relative: string}>  $paths */
        $paths = array_map(fn ($file) => [
            'absolute' => $file->getPathname(),
            'relative' => str_replace($projectRelativePath, '', $file->getPathname()),
        ], $files);

        // Exclude files more than 2 subdirectories deep.
        return array_filter(
            $paths,
            callback:
            /**
             * @param  array<int, array{absolute: string, relative: string}>  $path
             */
            function (array $path) use ($sourcePath) {
                $relativeFromSource = str_replace('/'.$sourcePath, '', $path['relative']);

                // There is a leading `/` so <= 3 includes paths up to 2 subdirectories deep.
                return substr_count($relativeFromSource, '/') <= 3;
            }
        );
    }
}
