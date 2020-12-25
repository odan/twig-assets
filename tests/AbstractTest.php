<?php

namespace Odan\Twig\Test;

use Odan\Twig\TwigAssetsEngine;
use Odan\Twig\TwigAssetsExtension;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * BaseTest.
 */
abstract class AbstractTest extends TestCase
{
    /**
     * @var FilesystemLoader
     */
    protected $loader;

    /**
     * @var Environment
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
     * <script src="/cache/file.96ce14164e1f92eb0ec93044a005be906f56d4.js"></script>.
     *
     * @var string
     */
    protected $scriptInlineFileRegex = '/^\<script src=\"file\.[a-zA-Z0-9]{36}/';

    /**
     * <link rel="stylesheet" type="text/css" href="file.c736045df3ebc9fc934d653ecb8738d0955d15.css" media="all" />.
     *
     * @var string
     */
    protected $styleInlineFileRegex = '/^\<link rel=\"stylesheet\" type=\"text\/css\" href=\"file\.[a-zA-Z0-9]{36}/';

    /**
     * @var array<mixed>
     */
    protected $options = [];

    /**
     * Set up.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->options = [
            // Public assets cache directory
            'path' => vfsStream::url('root/public/cache'),
            // The public url base path
            'url_base_path' => '',
            // Cache settings
            'cache_enabled' => true,
            'cache_path' => vfsStream::url('root/tmp'),
            'cache_name' => 'assets-cache',
            'cache_lifetime' => 0,
            'minify' => true,
        ];

        $this->root = vfsStream::setup('root');
        vfsStream::newDirectory('tmp/assets-cache')->at($this->root);
        vfsStream::newDirectory('public')->at($this->root);
        vfsStream::newDirectory('public/cache')->at($this->root);
        vfsStream::newDirectory('templates')->at($this->root);

        $templatePath = vfsStream::url('root/templates');
        $this->loader = new FilesystemLoader([$templatePath]);

        // Add alias path: @public/ -> root/public
        $this->loader->addPath(vfsStream::url('root/public'), 'public');

        $options = [
            'path' => $templatePath,
            'cache' => false,
            //'cache_path' =>  $config['temp'] . '/twig-cache'
        ];

        $this->env = new Environment($this->loader, $options);
        $this->extension = $this->newExtensionInstance();
    }

    /**
     * Create extension.
     *
     * @return TwigAssetsExtension The extension
     */
    public function newExtensionInstance(): TwigAssetsExtension
    {
        return new TwigAssetsExtension($this->env, $this->options);
    }

    /**
     * Create instance.
     *
     * @return TwigAssetsEngine The engine
     */
    public function newTwigAssetsEngineInstance(): TwigAssetsEngine
    {
        return new TwigAssetsEngine($this->env, $this->options);
    }
}
