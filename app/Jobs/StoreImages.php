<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class StoreImages implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $directory = __DIR__;
        $files = ['bruennhilde.jpg', 'morris.jpg']; // Replace with your actual filenames

        foreach ($files as $file) {
            $filePath = $directory . '/' . $file;
            if (is_file($filePath)) {
                $size = filesize($filePath);
                echo "File: $file, Size: $size bytes\n";
            } else {
                echo "File: $file does not exist\n";
            }
        }
        dd('got image info');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
    }
}
