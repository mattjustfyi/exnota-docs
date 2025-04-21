<!-- TOC -->
* [Exnota Docs](#exnota-docs)
  * [Useful commands](#useful-commands)
  * [Bugs](#bugs)
  * [Testing](#testing)
    * [Full sync](#full-sync)
    * [Creating a page](#creating-a-page-)
      * [create.js only (no job)](#createjs-only-no-job)
      * [Single job](#single-job)
      * [All jobs for sync](#all-jobs-for-sync)
    * [Updating a page](#updating-a-page)
      * [update.js only (no job)](#updatejs-only-no-job)
      * [Single job](#single-job-1)
      * [All jobs for sync](#all-jobs-for-sync-1)
    * [Home update](#home-update)
  * [PhpStorm](#phpstorm)
    * [Debugging node.js scripts](#debugging-nodejs-scripts)
      * [Directly](#directly)
      * [Called from PHP](#called-from-php)
    * [When listening for debug connections](#when-listening-for-debug-connections)
  * [PHPStan/Larastan](#phpstanlarastan)
    * [Factories](#factories)
    * [Eloquent/model relationships](#eloquentmodel-relationships)
    * [Queries](#queries)
  * [Troubleshooting](#troubleshooting)
    * [Property hooks and debugging](#property-hooks-and-debugging)
<!-- TOC -->

# Exnota Docs

## Useful commands

Run phpstan on project.

```bash
# Default PHP
vendor/bin/phpstan analyze --memory-limit 1024M

# Herd PHP
herd php vendor/bin/phpstan analyze --memory-limit 1024M

# Specific file
herd php vendor/bin/phpstan analyze --memory-limit 1024M app/Jobs/CreatePageJob.php
```

Re-generate models. Will need to revert changes to factories.

```bash
# Update existing migrations
# For use before we have production data to keep.
pa blueprint:build -m
```

Run some test code locally with full server context:

```bash
# php artisan tinker ...
pat --execute="include('tests/Manual/UpdateNotionHomeTest.php')"
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

## Workflow

1. Get a Github token
   * For now: Generate a classic personal access token with all 'repo' permissions: https://github.com/settings/tokens
   * TODO: Allow users to grant access to a Github App, and get token that way. This requires refreshing tokens etc. so implement later. https://docs.github.com/en/apps/creating-github-apps/registering-a-github-app/registering-a-github-app
2. Add a `repository` and `source` model, and the latter includes a `webhook_secret` value
   * Create repository: https://docs.exnota.com/repositories/create
     * URL in this format: https://api.github.com/repos/mttschltz/exnota-docs/contents
   * Create source: https://docs.exnota.com/sources/create
   * TODO: Add `webhook_secret` to repository model. Should be created when a repository is created. 
   * TODO: Update to validate webhook secret AND that it matches the repo: /Users/matt/code/exnota-docs/app/Jobs/GitPushWebhookValidator.php
3. In Github, add a webhook that points to docs.exnota.com/api/webhook/repository and passes the webhook secret
    * Where eto add it: https://github.com/mttschltz/exnota-docs/settings/hooks
4. TODO: Create Exnota Docs integration for Notion
   * For now, use Exnota integration.
5. Create home page with database in Notion
6. Authorize Exnota Docs to access home page in Notion
   * TODO: Create user flow for Notion auth, e.g. docs.exnota.com/notion/auth
   * For now, auth manually.
7. Add a `notion_database` and `notion_workspace` model, with token, DB ID and home ID.
   * TODO: Add filament resource for adding these via admin UI.

## Setting up an environment

### Product

1. Create a filament user
    ```bash
   php artisan make:filament-user
   ```
2. Login at `/login`



## Testing

### Full sync

```php
$source = App\Models\Source::find(1) 
App\Jobs\CreateSourceSyncJob::dispatch($source);1;
```

### Creating a page 

#### create.js only (no job)

Note: This will skip some cleanup, such as internal link removal as done by LinkUpdater.php. This could be done manually
using regexs from the class.

* From database: Construct JSON from database and send to create.js
  ```bash
  sqlite3 ../../database/database.sqlite "`cat ../../tools/export_data.sql`" | node --inspect-brk create.js
  ``

* From file
  ```bash
  cd cmd/notion-export
  node --inspect-brk create.js <<< '{"token": "<token>", "databaseId": "<notion database id>", "contentPath": "/Users/matt/code/exnota-docs/tests/Data/Docs/OneLevel/about/Values.md", "filePath":"/Users/matt/code/exnota-docs/tests/Data/Docs/OneLevel/releases/Release-process.md"}'
  ```

#### Single job

Existing document:

```php
$source = Source::find(1);
// Currently SourceSync isn't used much by CreatePageJob
$sourceSync = SourceSync::factory()->create(['source_id' => $source->id]);

$doc = Document::find(55); 

App\Jobs\CreatePageJob::dispatch($sourceSync, $doc);1; 
```

New document from file:

```php
$source = Source::find(1);
// Currently SourceSync isn't used much by CreatePageJob
$sourceSync = SourceSync::factory()->create(['source_id' => $source->id]);

$doc = Document::factory() \
	->contentFromFile('/Users/matt/code/exnota-docs/tests/Data/Docs/ThreeLevel/contributing/testing/Flutter-Test-Fonts.md') \
	->create(['source_id' => $source->id]);

 App\Jobs\CreatePageJob::dispatch($sourceSync, $doc);1; 
```

#### All jobs for sync

```bash
pat --execute="include('tests/Manual/CreatePageTest.php')"
```

### Updating a page

#### update.js only (no job)

Note: This will skip some cleanup, such as internal link removal as done by LinkUpdater.php. This could be done manually
using regexs from the class.

* From database: Construct JSON from database and send to update.js
    Note: I haven't added this yet. See [Creating a Page](#creating-a-page-) for an example.

* From file
  ```bash
  cd cmd/notion-export
  node --inspect-brk update.js <<< '{"token": "<token>", "pageID": "<notion page id>", "contentPath": "/Users/matt/code/exnota-docs/tests/Data/Docs/OneLevel/about/Values.md", "filePath":"/Users/matt/code/exnota-docs/tests/Data/Docs/OneLevel/releases/Release-process.md"}'
  ```

#### Single job

Existing document:

```php
$source = Source::find(1);
// Currently SourceSync isn't used much by UpdatePageJob
$sourceSync = SourceSync::factory()->create(['source_id' => $source->id]);

$doc = Document::find(55); 

App\Jobs\UpdatePageJob::dispatch($sourceSync, $doc);1; 
```

New document from file:

```php
$source = Source::find(1);
// Currently SourceSync isn't used much by UpdatePageJob
$sourceSync = SourceSync::factory()->create(['source_id' => $source->id]);

$doc = Document::factory() \
	->contentFromFile('/Users/matt/code/exnota-docs/tests/Data/Docs/ThreeLevel/contributing/testing/Flutter-Test-Fonts.md') \
	->create(['source_id' => $source->id]);

 App\Jobs\UpdatePageJob::dispatch($sourceSync, $doc);1; 
```

#### All jobs for sync

```bash
pat --execute="include('tests/Manual/UpdatePageTest.php')"
```

### Home update

Pre-requisite: Pages are already synced. You can use CreatePageTest.

```php
$source = Source::find(1);
// Currently SourceSync isn't used much by CreatePageJob
$sourceSync = SourceSync::factory()->create(['source_id' => $source->id]);

App\Jobs\UpdateNotionHomeJob::dispatch($sourceSync, $sourceSync->source->notionDatabase);1; 
```

or

```bash
# php artisan tinker ...
pat --execute="include('tests/Manual/UpdateNotionHomeTest.php')"
```


## PhpStorm

### Debugging node.js scripts

#### Directly

**Debug** the `Listen for Node.js` config and pass ` --inspect-brk` to `node` command.

Reference: https://www.jetbrains.com/help/phpstorm/running-and-debugging-node-js.html#node_debugging_running_application

#### Called from PHP

1. Set `APP_DEBUG_NODE=true` in .env
2. Run the PHP that calls the Node.js command.
3. You should see a log like this while Node.js pauses and waits for the debugger to be started to continue:
   ```bash
   [2025-02-11 08:49:58] local.INFO: Received log from create.js {"data":"Debugger listening on ws://127.0.0.1:9229/9181d41d-15cf-4a30-ab2b-c4f87cee328a
   ```
4. Run `Listen for Node.js`

### When listening for debug connections

It will pause every time it detects a new debug connection. This seems different from the
`PHP: Break at first line in PHP scripts` setting.

E.g. if you start a queue worker while listening, it will pause and you need to resume before the worker will actually
run. It is annoying.

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

## Libraries

### Notion

#### mariosimao/notion-sdk-php

- Doesn't support Link to Page blocks.

#### brd6/notion-sdk-php

- Columns don't work, even though there are classes for them.

## Troubleshooting

### Property hooks and debugging

Class properties defined using property hooks/accessors don't show values in `Threads & Variables` in PhpStorm.

Workaround: Don't use property hooks.
