<?php

declare(strict_types=1);

namespace App\Jobs;

class Folder
{
    public string $name;

    /**
     * @var array<string>
     *
     * @noinspection PhpGetterAndSetterCanBeReplacedWithPropertyHooksInspection we want a setter that accepts a single string and i don't think that can be done with a property hook.
     */
    private array $files = [];

    /**
     * @var array<Folder>
     *
     * @noinspection PhpGetterAndSetterCanBeReplacedWithPropertyHooksInspection We want a setter that accepts a single string and i don't think that can be done with a property hook.
     */
    private array $folders = [];

    /**
     * @return string[]
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    public function addFile(string $file): void
    {
        $this->files[] = $file;
    }

    /**
     * @return Folder[]
     */
    public function getFolders(): array
    {
        return $this->folders;
    }

    public function addFolder(Folder $folder): void
    {
        $this->folders[] = $folder;
    }
}
