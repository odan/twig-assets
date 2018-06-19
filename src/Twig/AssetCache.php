<?php

namespace Odan\Twig;

use RuntimeException;

/**
 * Asset Cache for public JS ans CSS files.
 */
class AssetCache
{
    /**
     * Cache.
     *
     * @var string Path
     */
    protected $publicDir;

    /**
     * Create new instance.
     *
     * @param string $publicDir
     *
     * @throws RuntimeException
     */
    public function __construct(string $publicDir)
    {
        $this->publicDir = $publicDir;

        if (!file_exists($this->publicDir)) {
            throw new RuntimeException("Path {$this->publicDir} not found");
        }
    }

    /**
     * Returns url for filename.
     *
     * @param string $fileName
     * @param string $content
     *
     * @return string
     */
    public function createCacheBustedUrl(string $fileName, string $content)
    {
        // For url we need to cache it
        $cacheFile = $this->createPublicCacheFile($fileName, $content);
        $name = pathinfo($cacheFile, PATHINFO_BASENAME);
        $dir = pathinfo($cacheFile, PATHINFO_DIRNAME);
        $dirs = explode('/', $dir);

        // Folder: cache/
        $cacheDirs = array_slice($dirs, count($dirs) - 1);

        // Folder: cache/filename.ext
        $path = implode('/', $cacheDirs) . '/' . $name;

        // Create url
        $cacheUrl = $path;

        return $cacheUrl;
    }

    /**
     * Create cache file from fileName.
     *
     * @param string $fileName
     * @param string $content
     *
     * @return string cacheFile
     */
    private function createPublicCacheFile(string $fileName, string $content): string
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (empty($extension)) {
            $extension = 'cache';
        }

        $name = pathinfo($fileName, PATHINFO_FILENAME);
        $checksum = sha1($fileName . $content);
        $cacheFile = $this->publicDir . '/' . $name . '.' . $checksum . '.' . $extension;

        file_put_contents($cacheFile, $content);
        chmod($cacheFile, 0775);

        return $cacheFile;
    }
}
