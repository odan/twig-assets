<?php

namespace Odan\Twig;

use Exception;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig Assets Extension.
 */
class TwigAssetsExtension extends AbstractExtension
{
    /**
     * @var TwigAssetsEngine
     */
    private $engine = null;

    /**
     * TwigAssetsExtension constructor.
     *
     * @param Environment $env
     * @param array $options
     *
     * @throws Exception
     */
    public function __construct(Environment $env, array $options)
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
        $function = new TwigFunction('assets', [$this, 'assets'], [
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
