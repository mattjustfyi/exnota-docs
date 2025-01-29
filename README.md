<!-- TOC -->
* [Exnota Docs](#exnota-docs)
  * [Useful commands](#useful-commands)
  * [Bugs](#bugs)
  * [Testing](#testing)
    * [Notion export: Directly to `create.js`](#notion-export-directly-to-createjs)
    * [Notion export: Single job](#notion-export-single-job-)
  * [PhpStorm](#phpstorm)
    * [When listening for debug connections](#when-listening-for-debug-connections-)
  * [PHPStan/Larastan](#phpstanlarastan)
    * [Factories](#factories)
    * [Eloquent/model relationships](#eloquentmodel-relationships)
    * [Queries](#queries)
<!-- TOC -->

# Exnota Docs

## Useful commands

Run phpstan on project.
```bash
vendor/bin/phpstan analyze --memory-limit 1024M
```
## Bugs

- Empty table cells need at least a space, if they are not the last row cell. 
  - E.g.
      ```markdown
      ### Error
      | Action Item | Owner | Issue | Notes |
      |-------------|-------|-------|-------|
      |action item||issue|notes|
    
      ### No error
      | Action Item | Owner | Issue | Notes |
      |-------------|-------|-------|-------|
      |action item| |issue||
      ```
  - Real example: `tests/Data/Docs/OneLevel/postmortems/Postmortem-Beta-1-Release.md`

## Testing

### Full sync

```php
$source = App\Models\Source::find(1) 
App\Jobs\CreateSourceSyncJob::dispatch($source);1;
```

### Export single document

* From database: Construct JSON from database and send to create.js
    ```bash
    sqlite3 ../../database/database.sqlite "`cat ../../tools/export_data.sql`" | node create.js
    ```

* From file
    ```bash
    cd cmd/notion-export
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
