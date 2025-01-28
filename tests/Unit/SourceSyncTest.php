<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\SourceSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class SourceSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_document_ids(): void
    {
        // If database has a JSON array of ints, it is correctly parsed by the attribute methods
        $initialSourceSync = SourceSync::factory()->create();
        DB::update('update source_syncs set document_ids = \'[1, 2, 98]\' where id = ?', [$initialSourceSync->id]);

        $updatedSourceSync = SourceSync::findOrFail($initialSourceSync->id);
        $this->assertEquals([1, 2, 98], $updatedSourceSync->document_ids);
    }

    public function test_set_document_ids(): void
    {
        // If document_ids is correctly set as an array of ints via Eloquent, it's persisted
        // to the database correctly.
        $initialSourceSync = SourceSync::factory()->create([
            'document_ids' => [13, 12, 11],
        ]);

        $this->assertEquals([13, 12, 11], $initialSourceSync->document_ids);

        /** @var array{document_ids:string}|null $fromDatabaseSourceSync */
        $fromDatabaseSourceSync = DB::selectOne('select * from source_syncs where id = ?', [$initialSourceSync->id]);
        $this->assertNotNull($fromDatabaseSourceSync);
        $this->assertEquals('[13,12,11]', $fromDatabaseSourceSync['document_ids']);
    }

    public function test_set_invalid_document_ids(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SourceSync::factory()->create([
            'document_ids' => ['13', '12', '11'],
        ]);
    }
}
