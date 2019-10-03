<?php

namespace Odan\Twig;

use Exception;
use InvalidArgumentException;
use JSMin\JSMin;
use RuntimeException;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use tubalmartin\CssMin\Minifier as CssMinifier;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Loader\LoaderInterface;

/**
 * Extension that adds the ability to cache and minify assets.
 */
class TwigAssetsEngine
{
    /**
     * @var LoaderInterface
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
     * @var PublicAssetsCache AssetCache
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
        'path_chmod' => 0750,
        'minify' => true,
        'inline' => false,
        'name' => 'file',
        'url_base_path' => null,
    ];

    /**
     * Create new instance.
     *
     * @param Environment $env Twig
     * @param array $options The options
     * - cache_adapter: The assets cache adapter. false or AbstractAdapter
     * - cache_name: Default is 'assets-cache'
     * - cache_lifetime: Default is 0
     * - cache_path: The temporary cache path
     * - path: The public assets cache directory (e.g. public/cache)
     * - url_base_path: The path of the minified css/js.
     * - minify: Enable JavaScript and CSS compression. The default value is true
     * - inline: Default is false
     * - name: The default asset name. The default value is 'file'
     *
     * @throws Exception
     */
    public function __construct(Environment $env, array $options)
    {
        $this->loader = $env->getLoader();

        $options = array_replace_recursive($this->options, $options);

        if (empty($options['path'])) {
            throw new InvalidArgumentException('The option [path] is not defined');
        }

        $chmod = -1;
        if (isset($options['path_chmod']) && $options['path_chmod'] > -1) {
            $chmod = (int)$options['path_chmod'];
        }

        $this->publicCache = new PublicAssetsCache($options['path'], $chmod);

        if (!empty($options['cache_path'])) {
            $this->cache = new FilesystemAdapter(
                $options['cache_name'],
                $options['cache_lifetime'],
                $options['cache_path']
            );
        } else {
            $this->cache = new ArrayAdapter();
        }

        unset($options['cache_adapter']);

        $this->options = $options;
    }

    /**
     * Render and compress JavaScript assets.
     *
     * @param array $assets Assets
     * @param array $options Options
     *
     * @return string content
     */
    public function assets(array $assets, array $options = []): string
    {
        $assets = $this->prepareAssets($assets);
        $options = (array)array_replace_recursive($this->options, $options);

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
     * @param array $assets Assets
     *
     * @return array assets
     */
    private function prepareAssets(array $assets): array
    {
        $result = [];
        foreach ($assets as $name) {
            $result[] = $this->getRealFilename($name);
        }

        return $result;
    }

    /**
     * Returns full path and filename.
     *
     * @param string $file File
     *
     * @throws LoaderError
     *
     * @return string
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
     * @param array $assets Assets
     * @param array $settings Settings
     *
     * @return string key
     */
    private function getCacheKey(array $assets, array $settings): string
    {
        $keys = [];
        foreach ($assets as $file) {
            $keys[] = sha1_file($file);
        }
        $keys[] = sha1(serialize($settings));

        return sha1(implode('', $keys));
    }

    /**
     * Render and compress CSS assets.
     *
     * @param array $assets Array of asset that would be embed to css
     * @param array $options Array of option / setting
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
        if ($public !== '') {
            $name = $options['name'] ?? 'file.css';

            if (empty(pathinfo($name, PATHINFO_EXTENSION))) {
                $name .= '.css';
            }

            $urlBasePath = $options['url_base_path'] ?? '';
            $url = $this->publicCache->createCacheBustedUrl($name, $public, $urlBasePath);

            $contents[] = sprintf('<link rel="stylesheet" type="text/css" href="%s" media="all" />', $url);
        }

        return implode("\n", $contents);
    }

    /**
     * Check if url is valid.
     *
     * @param string $url External url that to be validated
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
     * @throws RuntimeException
     *
     * @return string CSS code
     */
    public function getCssContent(string $fileName, bool $minify): string
    {
        $content = file_get_contents($fileName);

        if ($content === false) {
            throw new RuntimeException(sprintf('File could could not be read %s', $fileName));
        }

        if ($minify) {
            $compressor = new CssMinifier();
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
        if ($public !== '') {
            $name = $options['name'] ?? 'file.js';

            if (empty(pathinfo($name, PATHINFO_EXTENSION))) {
                $name .= '.js';
            }

            $urlBasePath = $options['url_base_path'] ?? '';
            $url = $this->publicCache->createCacheBustedUrl($name, $public, $urlBasePath);

            $contents[] = sprintf('<script src="%s"></script>', $url);
        }

        return implode("\n", $contents);
    }

    /**
     * Minimise JS.
     *
     * @param string $file Name of default JS file
     * @param bool $minify Minify js if true
     *
     * @throws RuntimeException
     *
     * @return string JavaScript code
     */
    private function getJsContent(string $file, bool $minify): string
    {
        $content = file_get_contents($file);

        if ($content === false) {
            throw new RuntimeException(sprintf('File could could not be read %s', $file));
        }

        if ($minify) {
            $content = JSMin::minify($content);
        }

        return $content;
    }
}
