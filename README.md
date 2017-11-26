# Twig Assets Extension

Caching and compression for Twig assets (JavaScript and CSS).

[![Latest Version on Packagist](https://img.shields.io/github/release/odan/twig-assets.svg)](https://github.com/odan/twig-assets/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE.md)
[![Build Status](https://travis-ci.org/odan/twig-assets.svg?branch=master)](https://travis-ci.org/odan/twig-assets)
[![Code Coverage](https://scrutinizer-ci.com/g/odan/twig-assets/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/odan/twig-assets/code-structure)
[![Quality Score](https://scrutinizer-ci.com/g/odan/twig-assets/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/odan/twig-assets/?branch=master)
[![Total Downloads](https://img.shields.io/packagist/dt/odan/twig-assets.svg)](https://packagist.org/packages/odan/twig-assets)


## Installation

```
composer install odan/twig-assets
```

## Configuration

```php
// Assets
$config['assets'] = [
    // Public assets cache directory
    'path' => '/var/www/example.com/public/assets',
    // Cache settings
    'cache_enabled' => true,
    'cache_path' => '/var/www/example.com/temp,
    'cache_name' => 'assets-cache',
    // Enable JavaScript and CSS compression
    'minify' => 1
];
```

## Slim Framework Twig View Installation

### Requriements

* [Slim Framework Twig View](https://github.com/slimphp/Twig-View)

### Container Setup

```
$container[\Slim\Views\Twig::class] = function (Container $container) {
    $settings = $container->get('settings');
    $viewPath = $settings['twig']['path'];

    $twig = new \Slim\Views\Twig($viewPath, [
        'cache' => $settings['twig']['cache_enabled'] ? $settings['twig']['cache_path']: false
    ]);

    /* @var Twig_Loader_Filesystem $loader */
    $loader = $twig->getLoader();
    $loader->addPath($settings['public'], 'public');

    // Instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace('index.php', '', $container->get('request')->getUri()->getBasePath()), '/');
    $twig->addExtension(new Slim\Views\TwigExtension($container->get('router'), $basePath));
    
    // Add the Assets extension to Twig
    $twig->addExtension(new \Odan\Twig\TwigAssetsExtension($twig->getEnvironment(), $settings['assets']));

    return $twig;
};
```

## Usage

### Template

Output cached and minified CSS content:

```twig
{{ assets({files: ['Login/login.css'], inline: false, public: true}) }}
```

Output multiple CSS assests into a single CSS file:

```twig
{{ assets({files: [
    '@public/css/default.css',
    '@public/css/print.css',
    'User/user-edit.css'
    ], inline: false, name: 'layout.css'})
}}
```

Output cached and minified JavaScript content:

```twig
{{ assets({files: ['Login/login.js'], inline: false, public: true}) }}
```

Output multiple CSS assests into a single CSS file:

```twig
{{ assets({files: [
    '@public/js/my-js-lib.js',
    '@public/js/notify.js',
    'Layout/app.js'
    ], inline: false, name: 'layout.js'})
}}
```

Output page specific assets:

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
    {{ assets({files: ['Home/home.js'], inline: false, public: true, name: 'home.js'}) }}
    {{ assets({files: ['Home/home.css'], inline: false, public: true, name: 'home.css'}) }}
{% endblock %}

{% block content %}
    <div id="content" class="container"></div>
{% endblock %}
```
