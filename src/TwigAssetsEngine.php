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
use Twig\Loader\LoaderInterface;

/**
 * Extension that adds the ability to cache and minify assets.
 */
final class TwigAssetsEngine
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
     * @var CssMinifier
     */
    private $cssMinifier;

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
    private $options;

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

        $options = array_replace_recursive([
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
        ], $options);

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

        $this->cssMinifier = new CssMinifier();
    }

    /**
     * Render and compress JavaScript assets.
     *
     * @param array $assets Assets
     * @param array $options Options
     * @param array $attributes Attributes
     *
     * @return string The content
     */
    public function assets(array $assets, array $options = [], array $attributes = []): string
    {
        $assets = $this->prepareAssets($assets);
        $options = (array)array_replace_recursive($this->options, $options);

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
        $cssContent = $this->css($cssFiles, $options, $attributes);
        $jsContent = $this->js($jsFiles, $options, $attributes);

        return $cssContent . $jsContent;
    }

    /**
     * Resolve real asset filenames.
     *
     * @param array $assets Assets
     *
     * @return array The assets
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
     * @return string The real filename
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
     * @return string The cache key
     */
    private function getCacheKey(array $assets, array $settings): string
    {
        $keys = [];
        foreach ($assets as $file) {
            $keys[] = sha1_file($file);
        }

        // Exclude nonce from cache
        unset($settings['nonce']);

        $keys[] = sha1((string)json_encode($settings));

        return sha1(implode('', $keys));
    }

    /**
     * Render and compress CSS assets.
     *
     * @param array $assets Array of asset that would be embed to css
     * @param array $options Array of option / setting
     * @param array $customAttributes Array of attributes to override default ones
     *
     * @return string The CSS content
     */
    public function css(array $assets, array $options, array $customAttributes): string
    {
        $contents = [];
        $content = '';

        foreach ($assets as $asset) {
            if ($this->isExternalUrl($asset)) {
                // External url
                $attributes = $this->createAttributes(array_merge(
                    [
                        'rel' => 'stylesheet',
                        'type' => 'text/css',
                        'href' => $asset,
                        'media' => 'all',
                    ],
                    $customAttributes
                ), $options);

                $contents[] = $this->element('link', $attributes, '', false);
                continue;
            }

            $fileContent = $this->getCssContent($asset, $options['minify']);

            if (!empty($options['inline'])) {
                $attributes = $this->createAttributes([], $options);
                $contents[] = $this->element('style', $attributes, $fileContent, true);
            } else {
                $content .= $fileContent . '';
            }
        }

        if ($content !== '') {
            $name = $options['name'] ?? 'file.css';

            if (empty(pathinfo($name, PATHINFO_EXTENSION))) {
                $name .= '.css';
            }

            $urlBasePath = $options['url_base_path'] ?? '';
            $url = $this->publicCache->createCacheBustedUrl($name, $content, $urlBasePath);

            $attributes = $this->createAttributes(array_merge(
                [
                    'rel' => 'stylesheet',
                    'type' => 'text/css',
                    'href' => $url,
                    'media' => 'all',
                ],
                $customAttributes
            ), $options);

            $contents[] = $this->element('link', $attributes, '', false);
        }

        return implode("\n", $contents);
    }

    /**
     * Render html element.
     *
     * @param string $name The element name
     * @param array $attributes The attributes
     * @param string $content The content
     * @param bool $closingTags Has closing tags
     *
     * @return string The html content
     */
    private function element(string $name, array $attributes, string $content, bool $closingTags): string
    {
        $attr = '';
        foreach ($attributes as $key => $value) {
            $attr .= sprintf(' %s="%s"', $key, htmlspecialchars($value));
        }

        $closingTag = $closingTags ? sprintf('>%s</%s>', $content, $name) : ' />';

        return sprintf('<%s%s%s', $name, $attr, $closingTag);
    }

    /**
     * Check if url is valid.
     *
     * @param string $url External url that to be validated
     *
     * @return bool The status
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
        $cacheKey = $this->getCacheKey([$fileName], ['minify' => $minify]);
        $cacheItem = $this->cache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        $content = file_get_contents($fileName);

        if ($content === false) {
            throw new RuntimeException(sprintf('File could could not be read %s', $fileName));
        }

        if ($minify) {
            $content = $this->cssMinifier->run($content);
        }

        $cacheItem->set($content);
        $this->cache->save($cacheItem);

        return $content;
    }

    /**
     * Render and compress CSS assets.
     *
     * @param array $assets Assets
     * @param array $options Options
     * @param array $customAttributes Array of attributes to override default ones
     *
     * @return string The content
     */
    public function js(array $assets, array $options, array $customAttributes): string
    {
        $contents = [];
        $content = '';

        foreach ($assets as $asset) {
            if ($this->isExternalUrl($asset)) {
                // External url
                $attributes = $this->createAttributes(
                    array_merge(
                        ['src' => $asset],
                        $customAttributes
                    ),
                    $options
                );
                $contents[] = $this->element('script', $attributes, '', true);

                continue;
            }

            $fileContent = $this->getJsContent($asset, (bool)$options['minify']);

            if (!empty($options['inline'])) {
                $attributes = $this->createAttributes([], $options);
                $contents[] = $this->element('script', $attributes, $fileContent, true);
            } else {
                $content .= sprintf("/* %s */\n", basename($asset)) . $fileContent . "\n";
            }
        }

        if ($content !== '') {
            $name = $options['name'] ?? 'file.js';

            if (empty(pathinfo($name, PATHINFO_EXTENSION))) {
                $name .= '.js';
            }

            $urlBasePath = $options['url_base_path'] ?? '';
            $url = $this->publicCache->createCacheBustedUrl($name, $content, $urlBasePath);
            $attributes = $this->createAttributes(
                array_merge(
                    ['src' => $url],
                    $customAttributes
                ),
                $options
            );
            $contents[] = $this->element('script', $attributes, '', true);
        }

        return implode("\n", $contents);
    }

    /**
     * Create array of html attributes.
     *
     * @param array $attributes The default values
     * @param array $options The options
     *
     * @return array The html attributes
     */
    private function createAttributes(array $attributes, array $options): array
    {
        if (!empty($options['nonce'])) {
            $attributes['nonce'] = $options['nonce'];
        }

        return $attributes;
    }

    /**
     * Minimize JS.
     *
     * @param string $file Name of default JS file
     * @param bool $minify Minify js if true
     *
     * @throws RuntimeException
     *
     * @return string The JavaScript code
     */
    private function getJsContent(string $file, bool $minify): string
    {
        $cacheKey = $this->getCacheKey([$file], ['minify' => $minify]);
        $cacheItem = $this->cache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        $content = file_get_contents($file);

        if ($content === false) {
            throw new RuntimeException(sprintf('File could could not be read %s', $file));
        }

        if ($minify) {
            $content = JSMin::minify($content);
        }

        $cacheItem->set($content);
        $this->cache->save($cacheItem);

        return $content;
    }
}
