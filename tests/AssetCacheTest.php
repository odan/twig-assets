<?php

namespace Odan\Test;

use Odan\Twig\AssetCache;
use org\bovigo\vfs\vfsStream;

/**
 * AssetCacheTest
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
     * @covers ::__construct
     */
    public function testInstance()
    {
        $this->assertInstanceOf(AssetCache::class, $this->newInstance());
    }

    /**
     * Test create object.
     *
     * @return void
     * @covers ::__construct
     * @expectedException \Exception
     */
    public function testInstanceError()
    {
        $cache = new AssetCache(vfsStream::url('root/nada'));
        $this->assertInstanceOf(AssetCache::class, $cache);
    }

    /**
     * Test
     *
     * @return void
     * @covers ::createCacheBustedUrl
     * @covers ::createPublicCacheFile
     */
    public function testCreateCacheBustedUrl()
    {
        $cache = $this->newInstance();
        $actual = $cache->createCacheBustedUrl(vfsStream::url('root/public/cache'), 'content');
        $this->assertRegExp($this->cacheBustedRegex, $actual);
    }
}
