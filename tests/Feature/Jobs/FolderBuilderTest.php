<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\FolderBuilder;
use App\Models\Document;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FolderBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_root_files_only(): void
    {
        /** @var Source $source */
        $source = Source::factory()->create([
            'path' => '/base/path/docs',
        ]);
        $documents = Document::factory()
            ->count(3)
            ->sequence(
                [
                    'path' => '/base/path/docs/root.md',
                ],
                [
                    'path' => '/base/path/docs/root2.md',
                ],
                [
                    'path' => '/base/path/docs/root3.md',
                ],
            )
            ->create([
                'source_id' => $source->id,
            ]);
        $folder = FolderBuilder::build($documents, $source);
        $this->assertEmpty($folder->getFolders());
        $this->assertEquals(['root.md', 'root2.md', 'root3.md'], $folder->getFiles());
    }

    public function test_build_three_levels_subfolders_with_multiples(): void
    {
        /** @var Source $source */
        $source = Source::factory()->create([
            'path' => '/base/path/docs',
        ]);
        $sequence = [
            [
                'path' => '/base/path/docs/zero-i.md',
            ],
            [
                'path' => '/base/path/docs/zero-ii.md',
            ],
            [
                'path' => '/base/path/docs/level1-a/one-i.md',
            ],
            [
                'path' => '/base/path/docs/level1-a/one-ii.md',
            ],
            [
                'path' => '/base/path/docs/level1-b/one-iii.md',
            ],
            [
                'path' => '/base/path/docs/level1-b/one-iv.md',
            ],
            [
                'path' => '/base/path/docs/level1-a/level2-a/two-i.md',
            ],
            [
                'path' => '/base/path/docs/level1-a/level2-a/two-ii.md',
            ],
            [
                'path' => '/base/path/docs/level1-a/level2-b/two-iii.md',
            ],
            [
                'path' => '/base/path/docs/level1-a/level2-b/two-iv.md',
            ],
            [
                'path' => '/base/path/docs/level1-a/level2-a/level3-a/three-i.md',
            ],
            [
                'path' => '/base/path/docs/level1-a/level2-a/level3-a/three-ii.md',
            ],
            [
                'path' => '/base/path/docs/level1-a/level2-a/level3-b/three-iii.md',
            ],
            [
                'path' => '/base/path/docs/level1-a/level2-a/level3-b/three-iv.md',
            ],
        ];
        $documents = Document::factory()
            ->count(count($sequence))
            ->sequence(...$sequence)
            ->create([
                'source_id' => $source->id,
            ]);
        $folder = FolderBuilder::build($documents, $source);
        $this->assertEquals(['zero-i.md', 'zero-ii.md'], $folder->getFiles());

        $level1Folders = $folder->getFolders();
        $this->assertCount(2, $level1Folders);
        $this->assertEquals('level1-a', $level1Folders[0]->name);
        $this->assertEquals(['one-i.md', 'one-ii.md'], $level1Folders[0]->getFiles());
        $this->assertEquals('level1-b', $level1Folders[1]->name);
        $this->assertEquals(['one-iii.md', 'one-iv.md'], $level1Folders[1]->getFiles());

        $level1aFolder = $level1Folders[0];
        $this->assertCount(2, $level1aFolder->getFolders());
        $this->assertEquals('level2-a', $level1aFolder->getFolders()[0]->name);
        $this->assertEquals(['two-i.md', 'two-ii.md'], $level1aFolder->getFolders()[0]->getFiles());
        $this->assertEquals('level2-b', $level1aFolder->getFolders()[1]->name);
        $this->assertEquals(['two-iii.md', 'two-iv.md'], $level1aFolder->getFolders()[1]->getFiles());

        $level2aFolder = $level1aFolder->getFolders()[0];
        $this->assertCount(2, $level2aFolder->getFolders());
        $this->assertEquals('level3-a', $level2aFolder->getFolders()[0]->name);
        $this->assertEquals(['three-i.md', 'three-ii.md'], $level2aFolder->getFolders()[0]->getFiles());
        $this->assertEquals('level3-b', $level2aFolder->getFolders()[1]->name);
        $this->assertEquals(['three-iii.md', 'three-iv.md'], $level2aFolder->getFolders()[1]->getFiles());
    }

    public function test_build_three_levels_only(): void
    {
        /** @var Source $source */
        $source = Source::factory()->create([
            'path' => '/base/path/docs',
        ]);
        $sequence = [
            [
                'path' => '/base/path/docs/level1/level2/level3/file-i.md',
            ],
            [
                'path' => '/base/path/docs/level1/level2/level3/file-ii.md',
            ],
        ];
        $documents = Document::factory()
            ->count(count($sequence))
            ->sequence(...$sequence)
            ->create([
                'source_id' => $source->id,
            ]);
        $folder = FolderBuilder::build($documents, $source);
        $this->assertEquals('root', $folder->name);
        $this->assertEquals([], $folder->getFiles());
        $this->assertCount(1, $folder->getFolders());

        $level1Folder = $folder->getFolders()[0];
        $this->assertEquals('level1', $level1Folder->name);
        $this->assertEquals([], $level1Folder->getFiles());
        $this->assertCount(1, $level1Folder->getFolders());

        $level2Folder = $level1Folder->getFolders()[0];
        $this->assertEquals('level2', $level2Folder->name);
        $this->assertEquals([], $level2Folder->getFiles());
        $this->assertCount(1, $level2Folder->getFolders());

        $level3Folder = $level2Folder->getFolders()[0];
        $this->assertEquals('level3', $level3Folder->name);
        $this->assertEquals(['file-i.md', 'file-ii.md'], $level3Folder->getFiles());
        $this->assertEmpty($level3Folder->getFolders());
    }
}
