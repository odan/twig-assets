<?php

namespace Odan\Twig\Test;

use Odan\Twig\TwigAssetsExtension;
use org\bovigo\vfs\vfsStream;

/**
 * AssetCacheTest.
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
     */
    public function testJsInline()
    {
        $file = vfsStream::newFile('test.js')->at($this->root)->setContent('alert(1);');
        $filename = $file->url();
        $actual = $this->extension->assets(['files' => [$filename], 'inline' => true]);
        $this->assertSame('<script>alert(1);</script>', $actual);

        // get from cache
        $actual2 = $this->extension->assets(['files' => [$filename], 'inline' => true]);
        $this->assertSame('<script>alert(1);</script>', $actual2);

        $file->setContent('alert(2);');
        $actual3 = $this->extension->assets(['files' => [$filename], 'inline' => true]);
        $this->assertSame('<script>alert(2);</script>', $actual3);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testJsDefault()
    {
        $file = vfsStream::newFile('test.js')->at($this->root)->setContent('alert(2);');
        $filename = $file->url();
        $actual = $this->extension->assets(['files' => [$filename], 'inline' => false]);
        $this->assertRegExp($this->scriptInlineFileRegex, $actual);

        // get from cache
        $actual2 = $this->extension->assets(['files' => [$filename], 'inline' => false]);
        $this->assertRegExp($this->scriptInlineFileRegex, $actual2);

        // update js file, cache must be rebuild
        file_put_contents($filename, 'alert(4);');
        $actual3 = $this->extension->assets(['files' => [$filename], 'inline' => false]);
        $this->assertRegExp($this->scriptInlineFileRegex, $actual3);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testJsPublic()
    {
        $file = vfsStream::newFile('public/test.js')->at($this->root)->setContent('alert(3);');
        $realFileUrl = $file->url();
        $filename = '@public/test.js';

        // Generate
        $actual = $this->extension->assets(['files' => [$filename], 'inline' => false]);
        $this->assertRegExp($this->scriptInlineFileRegex, $actual);

        // Get from cache
        $actual2 = $this->extension->assets(['files' => [$filename], 'inline' => false]);
        $this->assertRegExp($this->scriptInlineFileRegex, $actual2);

        // Update js file, cache must be rebuild
        file_put_contents($realFileUrl, 'alert(4);');
        $actual3 = $this->extension->assets(['files' => [$filename], 'inline' => false]);
        $this->assertRegExp($this->scriptInlineFileRegex, $actual3);
        $this->assertNotSame($actual2, $actual3);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testJsWithCustomAttributes()
    {
        $file = vfsStream::newFile('public/test.js')->at($this->root)->setContent('alert(3);');
        $realFileUrl = $file->url();
        $filename = '@public/test.js';

        $actual = $this->extension->assets(
            [
                'files' => [$filename],
                'attributes' => [
                    'type' => 'application/javascript'
                ],
                'inline' => false
            ]
        );

        $dom = new \DOMDocument();
        $dom->loadHTML($actual);
        $element = $dom->getElementsByTagName('script')->item(0);

        // new or overwritten attribute
        $this->assertSame('application/javascript', $element->getAttribute('type'));
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testCssDefault()
    {
        $content = 'body {
            /* background-color: #F4F4F4; */
            background-color: #f9fafa;
            /* background-color: #f8f8f8; */
            /* 60px to make the container go all the way to the bottom of the topbar */
            padding-top: 60px;
        }';

        $file = vfsStream::newFile('test.css')->at($this->root)->setContent($content);
        $filename = $file->url();
        $actual = $this->extension->assets(['files' => [$filename], 'inline' => false]);
        $this->assertRegExp($this->styleInlineFileRegex, $actual);

        // get from cache
        $actual2 = $this->extension->assets(['files' => [$filename], 'inline' => false]);
        $this->assertRegExp($this->styleInlineFileRegex, $actual2);

        // update css file, cache must be rebuild
        file_put_contents($filename, 'alert(4);');
        $actual3 = $this->extension->assets(['files' => [$filename], 'inline' => false]);
        $this->assertRegExp($this->styleInlineFileRegex, $actual3);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testCssWithCustomAttributes()
    {
        $content = 'body {
            /* background-color: #F4F4F4; */
            background-color: #f9fafa;
            /* background-color: #f8f8f8; */
            /* 60px to make the container go all the way to the bottom of the topbar */
            padding-top: 60px;
        }';

        $file = vfsStream::newFile('test.css')->at($this->root)->setContent($content);
        $filename = $file->url();

        $actual = $this->extension->assets(
            [
                'files'  => [$filename],
                'attributes' => [
                    'rel' => 'preload',
                    'as' => 'style',
                    'onload' => 'this.onload=null;this.rel=\'stylesheet\''
                ],
                'inline' => false,
            ]
        );

        $dom = new \DOMDocument();
        $dom->loadHTML($actual);
        $element = $dom->getElementsByTagName('link')->item(0);

        // new or overwritten attribute
        $this->assertSame('preload', $element->getAttribute('rel'));
        $this->assertSame('style', $element->getAttribute('as'));
        $this->assertSame('this.onload=null;this.rel=\'stylesheet\'', $element->getAttribute('onload'));

        //untouched default attribute
        $this->assertSame('all', $element->getAttribute('media'));
    }
}
