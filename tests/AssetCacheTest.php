<?php

namespace Odan\Twig\Test;

use Exception;
use Odan\Twig\PublicAssetsCache;
use org\bovigo\vfs\vfsStream;

/**
 * Test.
 *
 * @coversDefaultClass \Odan\Twig\PublicAssetsCache
 */
class AssetCacheTest extends AbstractTest
{
    /**
     * @var string
     */
    protected $cacheBustedRegex = '/^cache\/cache\.[a-zA-Z0-9]{36}/';

    /**
     * Create inctance.
     *
     * @return PublicAssetsCache
     */
    public function newInstance(): PublicAssetsCache
    {
        return new PublicAssetsCache(vfsStream::url('root/public/cache'), 0750);
    }

    /**
     * Test create object.
     *
     * @return void
     */
    public function testInstance(): void
    {
        $this->assertInstanceOf(PublicAssetsCache::class, $this->newInstance());
    }

    /**
     * Test create object.
     *
     * @return void
     */
    public function testInstanceError(): void
    {
        $this->expectException(Exception::class);
        $cache = new PublicAssetsCache(vfsStream::url('root/nada'));
        $this->assertInstanceOf(PublicAssetsCache::class, $cache);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testCreateCacheBustedUrl(): void
    {
        $cache = $this->newInstance();
        $actual = $cache->createCacheBustedUrl(vfsStream::url('root/public/cache'), 'content', 'cache/');
        $this->assertRegExp($this->cacheBustedRegex, $actual);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testCreateCacheBustedNormalUrl(): void
    {
        $cache = $this->newInstance();
        $actual = $cache->createCacheBustedUrl(vfsStream::url('root/public/cache/aa/file.js'), 'content', 'cache/');
        $this->assertSame('cache/file.5654d9a3d587a044a6d9d9ba34003c65bd036d97.js', $actual);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testCreateCacheBustedAdBlockedUrl(): void
    {
        $cache = $this->newInstance();
        $actual = $cache->createCacheBustedUrl(vfsStream::url('root/public/cache/ad/file.js'), 'content', 'cache/');
        $this->assertSame('cache/file.52f659a1fc90ca55c1d3f1ab8d2c4c2d573b676f.js', $actual);
    }
}
