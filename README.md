# Exnota Docs

## Useful commands

Run phpstan on project.
```bash
vendor/bin/phpstan analyze --memory-limit 1024M
```
## Testing

### Notion export: Directly to `create.js`

* From database: Construct JSON from database and send to create.js
    ```bash
    sqlite3 ../../database/database.sqlite "`cat ../../tools/export_data.sql`" | node create.js
    ```

* From file
    ```bash
    node create.js <<< '{"token": "<token>", "database_id": "<notion database id>", "content_path": "/Users/matt/code/exnota-docs/tests/Data/Docs/OneLevel/about/Values.md"}'
    ```
### Notion export: Single job 

```php
$source = Source::find(1);
// Currently SourceSync isn't used much by CreatePageJob
$sourceSync = SourceSync::factory()->create(['source_id' => $source->id]);

$doc = Document::factory() \
    ->contentFromFile('/Users/matt/code/exnota-docs/tests/Data/Docs/OneLevel/In-case-of-emergency.md')
    ->create(['source_id' => $source->id]);

 App\Jobs\CreatePageJob::dispatch($sourceSync, $doc);1; 
```

## PhpStorm

### When listening for debug connections 

It will pause every time it detects a new debug connection. This seems different from the `PHP: Break at first line in PHP scripts` setting.

E.g. if you start a queue worker while listening, it will pause and you need to resume before the worker will actually run. It is annoying.

## PHPStan/Larastan

### Factories

```php
/** @extends Factory<NotionWorkspace> */
class NotionWorkspaceFactory extends Factory
{
```

### Eloquent/model relationships

```php
/**
 * @return HasMany<NotionDatabase, $this>
 */
public function notionDatabases(): HasMany
{
```
More: https://github.com/larastan/larastan/blob/3.x/docs/features.md#model-relationships

### Queries

* `findOrFail` and similar may error if given ID is `mixed` 
