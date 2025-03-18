<?php

namespace Apie\Console;

use Apie\Core\Other\FileReaderInterface;
use Apie\Core\Other\FileWriterInterface;

final class ConsoleCliStorage
{
    private ?string $rootPath = null;

    private ?string $homePath = null;

    public function __construct(
        private readonly FileWriterInterface&FileReaderInterface $fileWriter
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    private function getHomePath(): string
    {
        if ($this->homePath) {
            return $this->homePath;
        }
        $home = getenv('HOME');
        if (empty($home)) {
            if (!empty($_SERVER['HOMEDRIVE']) && !empty($_SERVER['HOMEPATH'])) {
                // home on windows
                $home = $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH'];
            }
        }
        return $this->homePath = empty($home) ? $this->getRootPath() : $home;
    }

    private function getRootPath(): string
    {
        if ($this->rootPath) {
            return $this->rootPath;
        }
        $bt = debug_backtrace();
        if (isset($bt[0]['file'])) {
            return $this->rootPath = $bt[0]['file'];
        }
        // fallback
        $files = get_included_files();
        return $this->rootPath = reset($files) ? : __FILE__;
    }
    public function store(string $key, string $value): void
    {
        $filePath = $this->getHomePath() . '/.apie-' . md5($this->getRootPath() . '...' . $key) . '-cli';
        $this->fileWriter->writeFile($filePath, $value);
    }

    public function restore(string $key): ?string
    {
        $filePath = $this->getHomePath() . '/.apie-' . md5($this->getRootPath() . '...' . $key) . '-cli';
        return $this->fileWriter->fileExists($filePath) ? $this->fileWriter->readContents($filePath) : null;
    }

    public function remove(string $key): void
    {
        $filePath = $this->getHomePath() . '/.apie-' . md5($this->getRootPath() . '...' . $key) . '-cli';
        $this->fileWriter->clearPath($filePath);
    }
}
