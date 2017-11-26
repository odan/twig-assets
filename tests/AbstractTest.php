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
 * BaseTest
 */
abstract class AbstractTest extends TestCase
{
    /**
     * @var Twig_Loader_Filesystem
     */
    protected $loader;

    /**
     * @var Twig_Environment
     */
    protected $env;

    /**
     * @var TwigAssetsExtension
     */
    protected $extension;

    /**
     * @var vfsStreamDirectory
     */
    protected $root;

    /**
     * <script src="/cache/ab/file.96ce14164e1f92eb0ec93044a005be906f56d4.js"></script>
     *
     * @var string
     */
    protected $scriptInlineRegex = '/^\<script src=\"cache\/[a-zA-Z0-9]{2,2}\/file\.[a-zA-Z0-9]{36}/';

    /**
     * <link rel="stylesheet" type="text/css" href="cache/d6/file.c736045df3ebc9fc934d653ecb8738d0955d15.css" media="all" />
     *
     * @var string
     */
    protected $styleInlineRegex = '/^\<link rel=\"stylesheet\" type=\"text\/css\" href=\"cache\/[a-zA-Z0-9]{2,2}\/file\.[a-zA-Z0-9]{36}/';

    /**
     * Set up
     */
    public function setUp()
    {
        $this->root = vfsStream::setup('root');
        vfsStream::newDirectory('tmp/assets-cache')->at($this->root);
        vfsStream::newDirectory('public')->at($this->root);
        vfsStream::newDirectory('public/cache')->at($this->root);
        vfsStream::newDirectory('templates')->at($this->root);

        $templatePath = vfsStream::url('root/templates');
        $this->loader = new \Twig_Loader_Filesystem([$templatePath]);

        // Add alias path: @public/ -> root/public
        $this->loader->addPath(vfsStream::url('root/public'), 'public');

        $options = [
            'path' => $templatePath,
            'cache' => false,
            //'cache_path' =>  $config['temp'] . '/twig-cache'
        ];

        $this->env = new \Twig_Environment($this->loader, $options);
        $this->extension = $this->newExtensionInstance();
    }

    /**
     * @return TwigAssetsExtension
     */
    public function newExtensionInstance()
    {
        $options = [
            'template_path' => vfsStream::url('root/templates'),
            'cache' => new FilesystemAdapter(sha1(__DIR__), 0, vfsStream::url('root/tmp/assets-cache')),
            // 'public_dir' => vfsStream::url('root/public/cache'),
            'public_path' => vfsStream::url('root/public/cache'),
            'minify' => true
        ];

        return new TwigAssetsExtension($this->env, $this->loader, $options);
    }
}
