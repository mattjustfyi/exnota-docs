<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Brd6\NotionSdkPhp\Client;
use Brd6\NotionSdkPhp\ClientOptions;

class UpdateNotionPage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-notion-page';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // TODO: v1: No images, just update
        // 1. Get notion client
        // 2. Get list of Notion page children
        // 3. Delete all children
        // 4. Create new children
        // FIXME: Use env var
        $options = (new ClientOptions())
            ->setAuth(env('NOTION_AUTH_TOKEN'));

        $notion = new Client($options);

        // TODO: Needs paginaton
        $children = $notion->blocks()->children()->list('171944fbb2bc8034838af33758b0aedb');

        foreach ($children->getResults() as $child) {
            $notion->blocks()->delete($child->getId());
        }
        
        Log::info('StoreImages job handled');
        echo 'got image info';
    }
}
