# Twig Assets Extension

Caching and compression for Twig assets (JavaScript and CSS).

[![Latest Version on Packagist](https://img.shields.io/github/release/odan/twig-assets.svg)](https://github.com/odan/twig-assets/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE.md)
[![Build Status](https://travis-ci.org/odan/twig-assets.svg?branch=master)](https://travis-ci.org/odan/twig-assets)
[![Code Coverage](https://scrutinizer-ci.com/g/odan/twig-assets/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/odan/twig-assets/code-structure)
[![Quality Score](https://scrutinizer-ci.com/g/odan/twig-assets/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/odan/twig-assets/?branch=master)
[![Total Downloads](https://img.shields.io/packagist/dt/odan/twig-assets.svg)](https://packagist.org/packages/odan/twig-assets/stats)


## Installation

```
composer install odan/twig-assets
```

## Requirements

* PHP 7.0+

## Configuration

```php
$options = [
    // Public assets cache directory
    'path' => '/var/www/example.com/htdocs/public/assets/cache',
    
    // Public cache directory permissions (octal)
    // You need to prefix mode with a zero (0)
    // Use -1 to disable chmod
    'path_chmod' => 0750,
    
    // The public url base path
    'url_base_path' => 'assets/cache/',
    
    // Internal cache settings
    //
    // The main cache directory
    // Use '' (empty string) to disable the internal cache
    'cache_path' => '/var/www/example.com/htdocs/temp',
    
    // Used as the subdirectory of the cache_path directory, 
    // where cache items will be stored
    'cache_name' => 'assets-cache',
    
    // The lifetime (in seconds) for cache items
    // With a value 0 causing items to be stored indefinitely
    'cache_lifetime' => 0,
    
    // Enable JavaScript and CSS compression
    // 1 = on, 0 = off
    'minify' => 1
];
```

## Integration

### Register the Twig Extension

```php
$loader = new \Twig\Loader\FilesystemLoader('/path/to/templates');
$twig = new \Twig\Environment($loader, array(
    'cache' => '/path/to/compilation_cache',
));

$twig->addExtension(new \Odan\Twig\TwigAssetsExtension($twig, $options));
```

### Slim Framework

Requirements

* [Slim Framework Twig View](https://github.com/slimphp/Twig-View)

In your `dependencies.php` or wherever you add your Service Factories:

```php
$container[\Slim\Views\Twig::class] = function (Container $container) {
    $settings = $container->get('settings');
    $viewPath = $settings['twig']['path'];

    $twig = new \Slim\Views\Twig($viewPath, [
        'cache' => $settings['twig']['cache_enabled'] ? $settings['twig']['cache_path']: false
    ]);

    /** @var \Twig\Loader\FilesystemLoader $loader */
    $loader = $twig->getLoader();
    $loader->addPath($settings['public'], 'public');

    // Instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace('index.php', '', $container->get('request')->getUri()->getBasePath()), '/');
    $twig->addExtension(new \Slim\Views\TwigExtension($container->get('router'), $basePath));
    
    // Add the Assets extension to Twig
    $twig->addExtension(new \Odan\Twig\TwigAssetsExtension($twig->getEnvironment(), $settings['assets']));

    return $twig;
};
```

## Usage

### Custom template functions

This Twig extension exposes a custom `assets()` function to your Twig templates. You can use this function to generate complete URLs to any Slim application assets.

#### Parameters

Name | Type | Default | Required | Description
--- | --- | --- | --- | ---
files | array | [] | yes | All assets to be delivered to the browser. [Namespaced Twig Paths](http://symfony.com/doc/current/templating/namespaced_paths.html) (`@mypath/`) are also supported.
inline | bool | false | no | Defines whether the browser downloads the assets inline or via URL.
minify | bool | true | no | Specifies whether JS/CSS compression is enabled or disabled.
name | string | file | no | Defines the output file name within the URL.

### Template

#### Output cached and minified CSS content

```twig
{{ assets({files: ['Login/login.css']}) }}
```

Output cached and minified CSS content inline:

```twig
{{ assets({files: ['Login/login.css'], inline: true}) }}
```

Output multiple CSS assests into a single .css file:

```twig
{{ assets({files: [
    '@public/css/default.css',
    '@public/css/print.css',
    'User/user-edit.css'
    ], name: 'layout.css'})
}}
```

#### Output cached and minified JavaScript content

```twig
{{ assets({files: ['Login/login.js']}) }}
```

Output multiple JavaScript assests into a single .js file:

```twig
{{ assets({files: [
    '@public/js/my-js-lib.js',
    '@public/js/notify.js',
    'Layout/app.js'
    ], name: 'layout.js'})
}}
```

#### Output page specific assets

Content of file: `layout.twig`

```twig
<html>
    <head>
        {% block assets %}{% endblock %}
    </head>
    <body>
        {% block content %}{% endblock %}
    </body>
</html>
```

Content of `home.twig`:

```twig
{% extends "Layout/layout.twig" %}

{% block assets %}
    {{ assets({files: ['Home/home.js'], name: 'home.js'}) }}
    {{ assets({files: ['Home/home.css'], name: 'home.css'}) }}
{% endblock %}

{% block content %}
    <div id="content" class="container"></div>
{% endblock %}
```

## Configure a base path

You should inform the browser where to find the web assets with a `base href` in your layout template. 

### Slim Twig example:

```twig
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <!-- other stuff -->
    <base href="{{ base_url() }}/"/>
    <!-- other stuff -->
```
[Full example](https://github.com/odan/prisma/blob/master/templates/Layout/layout.twig#L10)

## Clearing the cache

### Clearing the internal cache

```php
use Odan\Twig\TwigAssetsCache;

$settings = $container->get('settings');

// Internal twig cache path e.g. tmp/twig-cache
$twigCachePath = $settings['twig']['cache_path']; 

$internalCache = new TwigAssetsCache($twigCachePath);
$internalCache->clearCache();
```

### Clearing the public cache

```php
use Odan\Twig\TwigAssetsCache;

$settings = $container->get('settings');

// Public assets cache directory e.g. 'public/cache' or 'public/assets'
$publicAssetsCachePath = $settings['assets']['path'];

$internalCache = new TwigAssetsCache($publicAssetsCachePath);
$internalCache->clearCache();
```
