<?php
/**
 * PimcoreConfigurationBundle
 * Copyright (c) Lukaschel
 */

namespace Lukaschel\PimcoreConfigurationBundle\Twig\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;

class BundleConfigurationExtension extends \Twig_Extension
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * BundleConfigurationExtension constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return array|\Twig_Function[]
     */
    public function getFunctions()
    {
        return [
            new \Twig_Function('bundleconfiguration', [$this, 'onBundleConfiguration']),
        ];
    }

    /**
     * @param string $key
     */
    public function onBundleConfiguration($key = '')
    {
        if (!$key) {
            return;
        }

        $service = $this->container->get('lukaschel.bundleconfiguration');

        return $service->getConfig($key);
    }
}
