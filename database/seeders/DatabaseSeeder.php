<?php

namespace Database\Seeders;

use App\Models\Repository;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Matt',
            'email' => 'email@matt.fyi',
            'password' => Hash::make('password'),
        ]);

        /** @noinspection LaravelFunctionsInspection */
        $repository = Repository::factory()->create([
            'url' => 'https://api.github.com/repos/mttschltz/exnota-docs/contents',
            'token' => env('GITHUB_TOKEN'),
        ]);

        $repository->sources()->create([
            'path' => '/tests/Data/Docs/OneLevel',
        ]);
    }
}
