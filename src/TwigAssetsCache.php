<?php

namespace Odan\Twig;

use DirectoryIterator;
use FilesystemIterator;
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
     * @var int File mode
     */
    protected $chmod = -1;

    /**
     * Create new instance.
     *
     * @param string $publicDir Public directory
     * @param int $chmod Changes file mode (optional)
     */
    public function __construct(string $publicDir, int $chmod = -1)
    {
        $this->directory = $publicDir;
        $this->chmod = $chmod;

        if (!file_exists($this->directory)) {
            throw new RuntimeException("Path {$this->directory} not found");
        }
    }

    /**
     * Clear the existing cache.
     *
     * @return bool Success
     */
    public function clearCache(): bool
    {
        $this->removeDirectory($this->directory);

        return mkdir($this->directory);
    }

    /**
     * Remove directory recursively.
     * This function is compatible with vfsStream.
     *
     * @param string $path Path
     *
     * @return bool true on success or false on failure
     */
    private function removeDirectory(string $path): bool
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

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            $fileName = $file->getPathname();
            unlink($fileName);
        }

        return rmdir($path);
    }
}
