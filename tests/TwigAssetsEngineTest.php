<?php

namespace Odan\Twig\Test;

use Exception;
use Odan\Twig\TwigAssetsEngine;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[UsesClass(TwigAssetsEngine::class)]
class TwigAssetsEngineTest extends TestCase
{
    use TwigTestTrait;

    /**
     * Test create object.
     *
     * @throws Exception
     *
     * @return void
     */
    public function testInstance(): void
    {
        $extension = $this->newTwigAssetsEngineInstance();
        $this->assertInstanceOf(TwigAssetsEngine::class, $extension);
    }
}
