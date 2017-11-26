<?php

namespace Odan\Test;

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
 * @coversDefaultClass \Odan\Twig\TwigAssetsExtension
 */
class TwigAssetsExtensionTest extends AbstractTest
{

    /**
     * Test create object.
     *
     * @return void
     */
    public function testInstance()
    {
        $extension = $this->newExtensionInstance();
        $this->assertInstanceOf(TwigAssetsExtension::class, $extension);
    }

    /**
     * Test.
     *
     * @return void
     * @covers ::getFunctions
     */
    public function testGetFunctions()
    {
        $extension = $this->newExtensionInstance();
        $this->assertNotEmpty($extension->getFunctions());
    }

    /**
     * Test.
     *
     * @return void
     * @covers ::assets
     */
    public function testJsInline()
    {
        $file = vfsStream::newFile('test.js')->at($this->root)->setContent('alert(1);');
        $filename = $file->url();
        $actual = $this->extension->assets(['files' => [$filename], ['inline' => true]]);
        $this->assertSame('<script>alert(1);</script>', $actual);

        // get from cache
        $actual2 = $this->extension->assets(['files' => [$filename], ['inline' => true]]);
        $this->assertSame('<script>alert(1);</script>', $actual2);

        $file->setContent('alert(2);');
        $actual3 = $this->extension->assets(['files' => [$filename], ['inline' => true]]);
        $this->assertSame('<script>alert(2);</script>', $actual3);
    }

    /**
     * Test.
     *
     * @return void
     * @covers ::assets
     * @covers \Odan\Twig\TwigAssetsEngine::assets
     * @covers \Odan\Twig\TwigAssetsEngine::prepareAssets
     * @covers \Odan\Twig\TwigAssetsEngine::js
     * @covers \Odan\Twig\TwigAssetsEngine::getJsContent
     * @covers \Odan\Twig\TwigAssetsEngine::getCacheKey
     * @covers \Odan\Twig\TwigAssetsEngine::isExternalUrl
     * @covers \Odan\Twig\TwigAssetsEngine::getRealFilename
     */
    public function testJsDefault()
    {
        $file = vfsStream::newFile('test.js')->at($this->root)->setContent('alert(2);');
        $filename = $file->url();
        $actual = $this->extension->assets(['files' => [$filename], 'inline' => false]);
        $this->assertRegExp($this->scriptInlineRegex, $actual);

        // get from cache
        $actual2 = $this->extension->assets(['files' => [$filename], 'inline' => false]);
        $this->assertRegExp($this->scriptInlineRegex, $actual2);

        // update js file, cache must be rebuild
        file_put_contents($filename, 'alert(4);');
        $actual3 = $this->extension->assets(['files' => [$filename], 'inline' => false]);
        $this->assertRegExp($this->scriptInlineRegex, $actual3);
    }

    /**
     * Test.
     *
     * @return void
     * @covers ::assets
     */
    public function testJsPublic()
    {
        $file = vfsStream::newFile('public/test.js')->at($this->root)->setContent('alert(3);');
        $realFileUrl = $file->url();
        $filename = '@public/test.js';

        // Generate
        $actual = $this->extension->assets(['files' => [$filename], 'inline' => false]);
        $this->assertRegExp($this->scriptInlineRegex, $actual);

        // Get from cache
        $actual2 = $this->extension->assets(['files' => [$filename], 'inline' => false]);
        $this->assertRegExp($this->scriptInlineRegex, $actual2);

        // Update js file, cache must be rebuild
        file_put_contents($realFileUrl, 'alert(4);');
        $actual3 = $this->extension->assets(['files' => [$filename], 'inline' => false]);
        $this->assertRegExp($this->scriptInlineRegex, $actual3);
        $this->assertNotEquals($actual2, $actual3);
    }

    /**
     * Test.
     *
     * @return void
     * @covers ::assets
     * @covers \Odan\Twig\TwigAssetsEngine::assets
     * @covers \Odan\Twig\TwigAssetsEngine::prepareAssets
     * @covers \Odan\Twig\TwigAssetsEngine::css
     * @covers \Odan\Twig\TwigAssetsEngine::getCssContent
     * @covers \Odan\Twig\TwigAssetsEngine::getCacheKey
     * @covers \Odan\Twig\TwigAssetsEngine::isExternalUrl
     * @covers \Odan\Twig\TwigAssetsEngine::getRealFilename
     */
    public function testCssDefault()
    {
        $content = "body {
            /* background-color: #F4F4F4; */
            background-color: #f9fafa;
            /* background-color: #f8f8f8; */
            /* 60px to make the container go all the way to the bottom of the topbar */
            padding-top: 60px;
        }";

        $file = vfsStream::newFile('test.css')->at($this->root)->setContent($content);
        $filename = $file->url();
        $actual = $this->extension->assets(['files' => [$filename], 'inline' => false]);
        $this->assertRegExp($this->styleInlineRegex, $actual);

        // get from cache
        $actual2 = $this->extension->assets(['files' => [$filename], 'inline' => false]);
        $this->assertRegExp($this->styleInlineRegex, $actual2);

        // update js file, cache must be rebuild
        file_put_contents($filename, 'alert(4);');
        $actual3 = $this->extension->assets(['files' => [$filename], 'inline' => false]);
        $this->assertRegExp($this->styleInlineRegex, $actual3);
    }
}
