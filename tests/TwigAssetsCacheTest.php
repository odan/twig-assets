<?php

namespace Odan\Twig\Test;

use Odan\Twig\TwigAssetsCache;
use org\bovigo\vfs\vfsStream;

/**
 * Test.
 *
 * @coversDefaultClass \Odan\Twig\TwigAssetsCache
 */
class TwigAssetsCacheTest extends AbstractTest
{
    /**
     * Test.
     *
     * @return void
     */
    public function testClearCache()
    {
        // Public assets cache directory e.g. 'public/cache' or 'public/assets'
        $cachePath = vfsStream::url('root/public/cache');

        vfsStream::newDirectory('public/cache/sub1')->at($this->root);
        vfsStream::newDirectory('public/cache/sub1/sub2')->at($this->root);

        file_put_contents($cachePath . '/test1.js', 'content');
        file_put_contents($cachePath . '/sub1/test2.js', 'content');
        file_put_contents($cachePath . '/sub1/sub2/test3.js', 'content');

        $internalCache = new TwigAssetsCache($cachePath);
        $internalCache->clearCache();

        $this->assertTrue(is_dir($cachePath));
        $this->assertFalse(file_exists($cachePath . '/test1.js'));
        $this->assertFalse(file_exists($cachePath . '/sub1/test2.js'));
        $this->assertFalse(file_exists($cachePath . '/sub1/sub2/test3.js'));
    }
}
