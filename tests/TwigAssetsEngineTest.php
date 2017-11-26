<?php

namespace Odan\Test;

use Odan\Twig\TwigAssetsEngine;
use Odan\Twig\TwigAssetsExtension;
use org\bovigo\vfs\vfsStreamDirectory;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Twig_Environment;
use Twig_Loader_Filesystem;

/**
 * AssetCacheTest
 *
 * @coversDefaultClass \Odan\Twig\TwigAssetsEngine
 */
class TwigAssetsEngineTest extends AbstractTest
{

    /**
     * @return TwigAssetsEngine
     */
    public function newTwigAssetsEngineInstance()
    {
        $options = [
            'template_path' => vfsStream::url('root/templates'),
            'cache' => new FilesystemAdapter(sha1(__DIR__), 0, vfsStream::url('root/tmp/assets-cache')),
            // 'public_dir' => vfsStream::url('root/public/cache'),
            'public_path' => vfsStream::url('root/public/cache'),
            'minify' => true
        ];
        return new TwigAssetsEngine($this->env, $this->loader, $options);
    }

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
