<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Brd6\NotionSdkPhp\Client;
use Brd6\NotionSdkPhp\ClientOptions;
use Symfony\Component\Process\Process;

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
        $options = (new ClientOptions())
            ->setAuth(env('NOTION_AUTH_TOKEN'));

        $notion = new Client($options);

        // TODO: Needs paginaton
        $children = $notion->blocks()->children()->list('171944fbb2bc8034838af33758b0aedb');

        foreach ($children->getResults() as $child) {
            $notion->blocks()->delete($child->getId());
        }


        // Save image
        // $filePath = __DIR__ . '/bruennhilde.jpg';
        // $s3Disk = Storage::disk('images');
        // $success = $s3Disk->put($filePath, file_get_contents($filePath));
        // if (!$success) {
        //     Log::error("Failed to upload $filePath to S3");
        //     throw new \Exception("Failed to upload $filePath to S3");
        // }
        // Log::info("Uploaded $filePath to S3");

        // /**
        //  * @disregard - disk->temporaryUrl() does exist but intelephense does not recognize it
        //  */
        // $tempURL = $s3Disk->temporaryUrl('bruennhilde.jpg', now()->addMinutes(5));


        // 4. Get markdown
        $markdown = file_get_contents(__DIR__ . '/onboarding.md');
        Log::info($markdown);

        // Call node to update
        $cmd = 'node cmd/notion-export/index.js --page 171944fbb2bc8034838af33758b0aedb --content "' . $markdown . '" --token ' . env('NOTION_AUTH_TOKEN');
        Process::fromShellCommandline($cmd)->run(function ($type, $data) {
            $this->info($data);
        });

        Log::info('StoreImages job handled');
        echo 'got image info';
    }
}
