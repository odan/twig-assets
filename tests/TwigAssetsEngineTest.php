<?php

namespace Odan\Test;

use Odan\Twig\TwigAssetsEngine;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use org\bovigo\vfs\vfsStream;

/**
 * AssetCacheTest
 *
 * @coversDefaultClass \Odan\Twig\TwigAssetsEngine
 */
class TwigAssetsEngineTest extends AbstractTest
{


    /**
     * Test create object.
     *
     * @return void
     * @covers ::__construct
     */
    public function testInstance()
    {
        $extension = $this->newTwigAssetsEngineInstance();
        $this->assertInstanceOf(TwigAssetsEngine::class, $extension);
    }
}
