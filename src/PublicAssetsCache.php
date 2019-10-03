<?php

namespace Odan\Twig;

/**
 * Asset Cache for public JS ans CSS files.
 */
class PublicAssetsCache extends TwigAssetsCache
{
    /**
     * Returns url for filename.
     *
     * @param string $fileName The filename
     * @param string $content The content
     * @param string $urlBasePath The url base path
     *
     * @return string The url
     */
    public function createCacheBustedUrl(string $fileName, string $content, string $urlBasePath): string
    {
        $cacheFile = $this->createPublicCacheFile($fileName, $content);

        return $urlBasePath . pathinfo($cacheFile, PATHINFO_BASENAME);
    }

    /**
     * Create cache file from fileName.
     *
     * @param string $fileName The filename
     * @param string $content The content
     *
     * @return string The cache filename
     */
    private function createPublicCacheFile(string $fileName, string $content): string
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (empty($extension)) {
            $extension = 'cache';
        }

        $name = pathinfo($fileName, PATHINFO_FILENAME);
        $checksum = sha1($fileName . $content);
        $cacheFile = $this->directory . '/' . $name . '.' . $checksum . '.' . $extension;

        file_put_contents($cacheFile, $content);

        if ($this->chmod > -1) {
            chmod($cacheFile, $this->chmod);
        }

        return $cacheFile;
    }
}
