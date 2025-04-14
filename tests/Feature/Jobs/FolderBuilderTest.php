<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\File;
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
                    'notion_id' => 'notion id 1',
                    'notion_url' => 'https://notion.url',
                ],
                [
                    'path' => '/base/path/docs/root2.md',
                    'notion_id' => 'notion id 2',
                    'notion_url' => 'https://notion.url',
                ],
                [
                    'path' => '/base/path/docs/root3.md',
                    'notion_id' => 'notion id 3',
                    'notion_url' => 'https://notion.url',
                ],
            )
            ->create([
                'source_id' => $source->id,
            ]);
        $folder = FolderBuilder::build($documents, $source);
        $this->assertEmpty($folder->getFolders());
        $files = $folder->getFiles();
        $this->assertCount(3, $files);
        $this->assertEquals(new File(name: 'root.md', notionID: 'notion id 1', notionURL: 'https://notion.url'), $files[0]);
        $this->assertEquals(new File(name: 'root2.md', notionID: 'notion id 2', notionURL: 'https://notion.url'), $files[1]);
        $this->assertEquals(new File(name: 'root3.md', notionID: 'notion id 3', notionURL: 'https://notion.url'), $files[2]);
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
                'notion_id' => 'notion id zero i',
                'notion_url' => 'https://notion.url',
            ],
            [
                'path' => '/base/path/docs/zero-ii.md',
                'notion_id' => 'notion id zero ii',
                'notion_url' => 'https://notion.url',
            ],
            [
                'path' => '/base/path/docs/level1-a/one-i.md',
                'notion_id' => 'notion id one i',
                'notion_url' => 'https://notion.url',
            ],
            [
                'path' => '/base/path/docs/level1-a/one-ii.md',
                'notion_id' => 'notion id one ii',
                'notion_url' => 'https://notion.url',
            ],
            [
                'path' => '/base/path/docs/level1-b/one-iii.md',
                'notion_id' => 'notion id one iii',
                'notion_url' => 'https://notion.url',
            ],
            [
                'path' => '/base/path/docs/level1-b/one-iv.md',
                'notion_id' => 'notion id one iv',
                'notion_url' => 'https://notion.url',
            ],
            [
                'path' => '/base/path/docs/level1-a/level2-a/two-i.md',
                'notion_id' => 'notion id two i',
                'notion_url' => 'https://notion.url',
            ],
            [
                'path' => '/base/path/docs/level1-a/level2-a/two-ii.md',
                'notion_id' => 'notion id two ii',
                'notion_url' => 'https://notion.url',
            ],
            [
                'path' => '/base/path/docs/level1-a/level2-b/two-iii.md',
                'notion_id' => 'notion id two iii',
                'notion_url' => 'https://notion.url',
            ],
            [
                'path' => '/base/path/docs/level1-a/level2-b/two-iv.md',
                'notion_id' => 'notion id two iv',
                'notion_url' => 'https://notion.url',
            ],
            [
                'path' => '/base/path/docs/level1-a/level2-a/level3-a/three-i.md',
                'notion_id' => 'notion id three i',
                'notion_url' => 'https://notion.url',
            ],
            [
                'path' => '/base/path/docs/level1-a/level2-a/level3-a/three-ii.md',
                'notion_id' => 'notion id three ii',
                'notion_url' => 'https://notion.url',
            ],
            [
                'path' => '/base/path/docs/level1-a/level2-a/level3-b/three-iii.md',
                'notion_id' => 'notion id three iii',
                'notion_url' => 'https://notion.url',
            ],
            [
                'path' => '/base/path/docs/level1-a/level2-a/level3-b/three-iv.md',
                'notion_id' => 'notion id three iv',
                'notion_url' => 'https://notion.url',
            ],
        ];
        $documents = Document::factory()
            ->count(count($sequence))
            ->sequence(...$sequence)
            ->create([
                'source_id' => $source->id,
            ]);
        $folder = FolderBuilder::build($documents, $source);
        $this->assertEquals(
            [
                new File(name: 'zero-i.md', notionID: 'notion id zero i', notionURL: 'https://notion.url'),
                new File(name: 'zero-ii.md', notionID: 'notion id zero ii', notionURL: 'https://notion.url'),
            ],
            $folder->getFiles()
        );

        $level1Folders = $folder->getFolders();
        $this->assertCount(2, $level1Folders);
        $this->assertEquals('Level1-a', $level1Folders[0]->name);
        $this->assertEquals(
            [
                new File(name: 'one-i.md', notionID: 'notion id one i', notionURL: 'https://notion.url'),
                new File(name: 'one-ii.md', notionID: 'notion id one ii', notionURL: 'https://notion.url'),
            ],
            $level1Folders[0]->getFiles()
        );
        $this->assertEquals('Level1-b', $level1Folders[1]->name);
        $this->assertEquals(
            [
                new File(name: 'one-iii.md', notionID: 'notion id one iii', notionURL: 'https://notion.url'),
                new File(name: 'one-iv.md', notionID: 'notion id one iv', notionURL: 'https://notion.url'),
            ],
            $level1Folders[1]->getFiles()
        );

        $level1aFolder = $level1Folders[0];
        $this->assertCount(2, $level1aFolder->getFolders());
        $this->assertEquals('Level2-a', $level1aFolder->getFolders()[0]->name);
        $this->assertEquals(
            [
                new File(name: 'two-i.md', notionID: 'notion id two i', notionURL: 'https://notion.url'),
                new File(name: 'two-ii.md', notionID: 'notion id two ii', notionURL: 'https://notion.url'),
            ],
            $level1aFolder->getFolders()[0]->getFiles()
        );
        $this->assertEquals('Level2-b', $level1aFolder->getFolders()[1]->name);
        $this->assertEquals(
            [
                new File(name: 'two-iii.md', notionID: 'notion id two iii', notionURL: 'https://notion.url'),
                new File(name: 'two-iv.md', notionID: 'notion id two iv', notionURL: 'https://notion.url'),
            ],
            $level1aFolder->getFolders()[1]->getFiles()
        );

        $level2aFolder = $level1aFolder->getFolders()[0];
        $this->assertCount(2, $level2aFolder->getFolders());
        $this->assertEquals('Level3-a', $level2aFolder->getFolders()[0]->name);
        $this->assertEquals(
            [
                new File(name: 'three-i.md', notionID: 'notion id three i', notionURL: 'https://notion.url'),
                new File(name: 'three-ii.md', notionID: 'notion id three ii', notionURL: 'https://notion.url'),
            ],
            $level2aFolder->getFolders()[0]->getFiles()
        );
        $this->assertEquals('Level3-b', $level2aFolder->getFolders()[1]->name);
        $this->assertEquals(
            [
                new File(name: 'three-iii.md', notionID: 'notion id three iii', notionURL: 'https://notion.url'),
                new File(name: 'three-iv.md', notionID: 'notion id three iv', notionURL: 'https://notion.url'),
            ],
            $level2aFolder->getFolders()[1]->getFiles()
        );
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
                'notion_id' => 'notion id file i',
                'notion_url' => 'https://notion.url',
            ],
            [
                'path' => '/base/path/docs/level1/level2/level3/file-ii.md',
                'notion_id' => 'notion id file ii',
                'notion_url' => 'https://notion.url',
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
        $this->assertEquals('Level1', $level1Folder->name);
        $this->assertEquals([], $level1Folder->getFiles());
        $this->assertCount(1, $level1Folder->getFolders());

        $level2Folder = $level1Folder->getFolders()[0];
        $this->assertEquals('Level2', $level2Folder->name);
        $this->assertEquals([], $level2Folder->getFiles());
        $this->assertCount(1, $level2Folder->getFolders());

        $level3Folder = $level2Folder->getFolders()[0];
        $this->assertEquals('Level3', $level3Folder->name);
        $this->assertEquals(
            [
                new File(name: 'file-i.md', notionID: 'notion id file i', notionURL: 'https://notion.url'),
                new File(name: 'file-ii.md', notionID: 'notion id file ii', notionURL: 'https://notion.url'),
            ],
            $level3Folder->getFiles()
        );
        $this->assertEmpty($level3Folder->getFolders());
    }
}
