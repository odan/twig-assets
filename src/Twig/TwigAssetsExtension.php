<?php

namespace Odan\Twig;

use Twig_Environment;
use Twig_Extension;
use Twig_SimpleFunction;

/**
 * TwigAssetsExtension.
 */
class TwigAssetsExtension extends Twig_Extension
{
    /**
     * @var TwigAssetsEngine
     */
    //private $env;

    private $engine = null;

    /**
     * TwigAssetsExtension constructor.
     *
     * @param Twig_Environment $env
     * @param array $options
     */
    public function __construct(Twig_Environment $env, array $options)
    {
        $this->engine = new TwigAssetsEngine($env, $options);
    }

    /**
     * Get functions.
     *
     * @return array
     */
    public function getFunctions()
    {
        $function = new Twig_SimpleFunction('assets', [$this, 'assets'], [
            'needs_environment' => false,
            'is_safe' => ['html'],
        ]);

        $function->setArguments([]);

        return [$function];
    }

    /**
     * Assets function.
     *
     * @return string
     */
    public function assets()
    {
        $params = func_get_args();
        $assets = $params[0]['files'];
        unset($params[0]['files']);
        $options = $params[0];

        return $this->engine->assets($assets, $options);
    }
}
