<?php

namespace Odan\Twig;

use RuntimeException;
use SplFileInfo;

/**
 * Asset Cache for the internal JS ans CSS files.
 */
class TwigAssetsCache
{
    /**
     * Cache.
     *
     * @var string Path
     */
    protected $directory;

    /**
     * Create new instance.
     *
     * @param string $publicDir
     *
     * @throws RuntimeException
     */
    public function __construct(string $publicDir)
    {
        $this->directory = $publicDir;

        if (!file_exists($this->directory)) {
            throw new RuntimeException("Path {$this->directory} not found");
        }
    }

    /**
     * Clear the existing cache.
     *
     * @return bool
     */
    public function clearCache(): bool
    {
        return $this->removeDirectory($this->directory);
    }

    /**
     * Remove directory recursively.
     * This function is compatible with vfsStream.
     *
     * @param string $path Path
     * @return bool True on success or false on failure.
     */
    private function removeDirectory($path): bool
    {
        $iterator = new DirectoryIterator($path);
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDot() || !$fileInfo->isDir()) {
                continue;
            }
            $dirName = $fileInfo->getPathname();
            $this->removeDirectory($dirName);
        }

        $files = new FilesystemIterator($path);

        /* @var SplFileInfo $file */
        foreach ($files as $file) {
            $fileName = $file->getPathname();
            unlink($fileName);
        }

        return rmdir($path);
    }
}
