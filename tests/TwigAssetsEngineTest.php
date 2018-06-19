<?php

namespace Odan\Test;

use Exception;
use Odan\Twig\TwigAssetsEngine;

/**
 * AssetCacheTest.
 *
 * @coversDefaultClass \Odan\Twig\TwigAssetsEngine
 */
class TwigAssetsEngineTest extends AbstractTest
{
    /**
     * Test create object.
     *
     * @return void
     * @throws Exception
     */
    public function testInstance()
    {
        $extension = $this->newTwigAssetsEngineInstance();
        $this->assertInstanceOf(TwigAssetsEngine::class, $extension);
    }
}
