<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StoreImages implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        Log::info('StoreImages job created');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $directory = __DIR__;
        $files = ['bruennhilde.jpg', 'morris.jpg'];

        $s3Disk = Storage::disk('images');
        foreach ($files as $file) {
            $filePath = $directory . '/' . $file;
            if (is_file($filePath)) {
                $success = $s3Disk->put($file, file_get_contents($filePath));
                if (!$success) {
                    Log::error("Failed to upload $file to S3");
                    throw new \Exception("Failed to upload $file to S3");
                }
                Log::info("Uploaded $file to S3");
                /**
                 * @disregard - disk->url() does exist but intelephense does not recognize it
                 */
                $permaURL = $s3Disk->url($file);
                Log::info($permaURL);

                /**
                 * @disregard - disk->url() does exist but intelephense does not recognize it
                 */
                $tempURL = $s3Disk->temporaryUrl($file, now()->addMinutes(5));
                Log::info($tempURL);
            } else {
                Log::error("File: $file does not exist");
                throw new \Exception("File: $file does not exist");
            }
        }

        Log::info('StoreImages job handled');
        echo 'got image info';
    }
}
