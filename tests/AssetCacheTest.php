<?php

namespace Odan\Test;

use Exception;
use Odan\Twig\AssetCache;
use org\bovigo\vfs\vfsStream;

/**
 * AssetCacheTest.
 *
 * @coversDefaultClass \Odan\Twig\AssetCache
 */
class AssetCacheTest extends AbstractTest
{
    protected $cacheBustedRegex = '/^cache\/cache\.[a-zA-Z0-9]{36}/';

    /**
     * @return AssetCache
     */
    public function newInstance()
    {
        return new AssetCache(vfsStream::url('root/public/cache'));
    }

    /**
     * Test create object.
     *
     * @return void
     */
    public function testInstance()
    {
        $this->assertInstanceOf(AssetCache::class, $this->newInstance());
    }

    /**
     * Test create object.
     *
     * @return void
     * @expectedException Exception
     */
    public function testInstanceError()
    {
        $cache = new AssetCache(vfsStream::url('root/nada'));
        $this->assertInstanceOf(AssetCache::class, $cache);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testCreateCacheBustedUrl()
    {
        $cache = $this->newInstance();
        $actual = $cache->createCacheBustedUrl(vfsStream::url('root/public/cache'), 'content');
        $this->assertRegExp($this->cacheBustedRegex, $actual);
    }


    /**
     * Test.
     *
     * @return void
     */
    public function testCreateCacheBustedNormalUrl()
    {
        $cache = $this->newInstance();
        $actual = $cache->createCacheBustedUrl(vfsStream::url('root/public/cache/aa/file.js'), 'content');
        $this->assertSame('/cache/file.5654d9a3d587a044a6d9d9ba34003c65bd036d97.js', $actual);
    }


    /**
     * Test.
     *
     * @return void
     */
    public function testCreateCacheBustedAdBlockedUrl()
    {
        $cache = $this->newInstance();
        $actual = $cache->createCacheBustedUrl(vfsStream::url('root/public/cache/ad/file.js'), 'content');
        $this->assertSame('/cache/file.52f659a1fc90ca55c1d3f1ab8d2c4c2d573b676f.js', $actual);
    }
}
