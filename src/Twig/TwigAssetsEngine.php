<?php

namespace Odan\Twig;

use Exception;
use Odan\CssMin\CssMinify;
use Odan\JsMin\JsMinify;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Twig_Environment;
use Twig_Error_Loader;
use Twig_Loader_Filesystem;
use Twig_LoaderInterface;

/**
 * Extension that adds the ability to cache and minify assets.
 */
class TwigAssetsEngine
{
    /**
     * @var Twig_Environment
     */
    private $env;

    /**
     * @var Twig_Loader_Filesystem|Twig_LoaderInterface
     */
    private $loader;

    /**
     * Cache.
     *
     * @var AbstractAdapter|ArrayAdapter
     */
    private $cache;

    /**
     * Cache.
     *
     * @var AssetCache AssetCache
     */
    private $publicCache;

    /**
     * Default options.
     *
     * @var array
     */
    private $options = [
        'cache_adapter' => null,
        'cache_name' => 'assets-cache',
        'cache_lifetime' => 0,
        'cache_path' => null,
        'path' => null,
        'minify' => true,
        'inline' => false,
        'name' => 'file',
    ];

    /**
     * Create new instance.
     *
     * @param Twig_Environment $env
     * @param array $options
     * - cache_adapter: The assets cache adapter. false or AbstractAdapter
     * - cache_name: Default is 'assets-cache'
     * - cache_lifetime: Default is 0
     * - cache_path: The temporary cache path
     * - path: The public assets cache directory (e.g. public/cache)
     * - minify: Enable JavaScript and CSS compression. The default value is true
     * - inline: Default is false
     * - name: The default asset name. The default value is 'file'
     *
     * @throws Exception
     */
    public function __construct(Twig_Environment $env, array $options)
    {
        $this->env = $env;
        $this->loader = $env->getLoader();

        $options = array_replace_recursive($this->options, $options);

        if (empty($options['path'])) {
            throw new Exception('The option [path] is not defined');
        }
        $this->publicCache = new AssetCache($options['path']);

        if (!empty($options['cache_path'])) {
            $this->cache = new FilesystemAdapter($options['cache_name'], $options['cache_lifetime'], $options['cache_path']);
        } else {
            $this->cache = new ArrayAdapter();
        }

        unset($options['cache_adapter']);

        $this->options = $options;
    }

    /**
     * Render and compress JavaScript assets.
     *
     * @param array $assets
     * @param array $options
     *
     * @return string content
     * @throws InvalidArgumentException
     */
    public function assets(array $assets, array $options = []): string
    {
        $assets = $this->prepareAssets($assets);
        $options = array_replace_recursive($this->options, $options);

        $cacheKey = $this->getCacheKey($assets, $options);
        $cacheItem = $this->cache->getItem($cacheKey);
        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        $jsFiles = [];
        $cssFiles = [];
        foreach ($assets as $file) {
            $fileType = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if ($fileType == 'js') {
                $jsFiles[] = $file;
            }
            if ($fileType == 'css') {
                $cssFiles[] = $file;
            }
        }
        $cssContent = $this->css($cssFiles, $options);
        $jsContent = $this->js($jsFiles, $options);
        $result = $cssContent . $jsContent;

        $cacheItem->set($result);
        $this->cache->save($cacheItem);

        return $result;
    }

    /**
     * Resolve real asset filenames.
     *
     * @param mixed $assets
     *
     * @return array
     */
    private function prepareAssets($assets)
    {
        $result = [];
        foreach ((array)$assets as $name) {
            $result[] = $this->getRealFilename($name);
        }

        return $result;
    }

    /**
     * Returns full path and filename.
     *
     * @param string $file
     *
     * @return string
     * @throws Twig_Error_Loader
     */
    private function getRealFilename(string $file): string
    {
        if (strpos($file, 'vfs://') !== false) {
            return $file;
        }

        return $this->loader->getSourceContext($file)->getPath();
    }

    /**
     * Get cache key.
     *
     * @param mixed $assets
     * @param mixed $settings
     *
     * @return string
     */
    private function getCacheKey($assets, $settings = null)
    {
        $keys = [];
        foreach ((array)$assets as $file) {
            $keys[] = sha1_file($file);
        }
        $keys[] = sha1(serialize($settings));

        return sha1(implode('', $keys));
    }

    /**
     * Render and compress CSS assets.
     *
     * @param array $assets
     * @param array $options
     *
     * @return string content
     */
    public function css(array $assets, array $options): string
    {
        $contents = [];
        $public = '';
        foreach ($assets as $asset) {
            if ($this->isExternalUrl($asset)) {
                // External url
                $contents[] = sprintf('<link rel="stylesheet" type="text/css" href="%s" media="all" />', $asset);
                continue;
            }
            $content = $this->getCssContent($asset, $options['minify']);

            if (!empty($options['inline'])) {
                $contents[] = sprintf('<style>%s</style>', $content);
            } else {
                $public .= $content . '';
            }
        }
        if (strlen($public) > 0) {
            $name = isset($options['name']) ? $options['name'] : 'file.css';
            if (empty(pathinfo($name, PATHINFO_EXTENSION))) {
                $name .= '.css';
            }
            $url = $this->publicCache->createCacheBustedUrl($name, $public);
            $contents[] = sprintf('<link rel="stylesheet" type="text/css" href="%s" media="all" />', $url);
        }
        $result = implode("\n", $contents);

        return $result;
    }

    /**
     * Check if url is valid.
     *
     * @param string $url
     *
     * @return bool
     */
    private function isExternalUrl($url): bool
    {
        return (!filter_var($url, FILTER_VALIDATE_URL) === false) && (strpos($url, 'vfs://') === false);
    }

    /**
     * Minimize CSS.
     *
     * @param string $fileName Name of default CSS file
     * @param bool $minify Minify css if true
     *
     * @return string CSS code
     */
    public function getCssContent(string $fileName, bool $minify)
    {
        $content = file_get_contents($fileName);
        if ($minify) {
            $compressor = new CssMinify();
            $content = $compressor->run($content);
        }

        return $content;
    }

    /**
     * Render and compress CSS assets.
     *
     * @param array $assets Assets
     * @param array $options Options
     *
     * @return string content
     */
    public function js(array $assets, array $options): string
    {
        $contents = [];
        $public = '';
        foreach ($assets as $asset) {
            if ($this->isExternalUrl($asset)) {
                // External url
                $contents[] = sprintf('<script src="%s"></script>', $asset);
                continue;
            }
            $content = $this->getJsContent($asset, $options['minify']);

            if (!empty($options['inline'])) {
                $contents[] = sprintf('<script>%s</script>', $content);
            } else {
                $public .= sprintf("/* %s */\n", basename($asset)) . $content . "\n";
            }
        }
        if (strlen($public) > 0) {
            $name = isset($options['name']) ? $options['name'] : 'file.js';
            if (empty(pathinfo($name, PATHINFO_EXTENSION))) {
                $name .= '.js';
            }
            $url = $this->publicCache->createCacheBustedUrl($name, $public);
            $contents[] = sprintf('<script src="%s"></script>', $url);
        }
        $result = implode("\n", $contents);

        return $result;
    }

    /**
     * Minimise JS.
     *
     * @param string $file Name of default JS file
     * @param bool $minify Minify js if true
     *
     * @return string JavaScript code
     */
    private function getJsContent(string $file, bool $minify)
    {
        $content = file_get_contents($file);
        if ($minify) {
            $content = JsMinify::minify($content);
        }

        return $content;
    }
}
