<?php

namespace Odan\Twig\Test;

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
     * @throws Exception
     *
     * @return void
     */
    public function testInstance()
    {
        $extension = $this->newTwigAssetsEngineInstance();
        $this->assertInstanceOf(TwigAssetsEngine::class, $extension);
    }
}
