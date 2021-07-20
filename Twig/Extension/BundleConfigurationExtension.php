<?php
/**
 * PimcoreConfigurationBundle
 * Copyright (c) Lukaschel
 */

declare(strict_types=1);

namespace Lukaschel\PimcoreConfigurationBundle\Twig\Extension;

use Lukaschel\PimcoreConfigurationBundle\Components\BundleConfiguration;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class BundleConfigurationExtension extends AbstractExtension
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('bundleconfiguration', [$this, 'onBundleConfiguration']),
        ];
    }

    /**
     * @param string $key
     * @return array|false|void
     */
    public function onBundleConfiguration(string $key = '')
    {
        if (empty($key)) {
            return;
        }

        /** @var BundleConfiguration $service */
        $service = $this->container->get('lukaschel.bundleconfiguration');

        return $service->getConfig($key);
    }
}
