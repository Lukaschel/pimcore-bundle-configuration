<?php
/**
 * PimcoreConfigurationBundle
 * Copyright (c) Lukaschel
 */

declare(strict_types=1);

namespace Lukaschel\PimcoreConfigurationBundle;

use Lukaschel\PimcoreConfigurationBundle\Tools\Installer;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;

class PimcoreConfigurationBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    public const PACKAGE_NAME = 'lukaschel/pimcore-bundle-configuration';

    public function getVersion(): string
    {
        return '2.0.0';
    }

    public function getInstaller()
    {
        return $this->container->get(Installer::class);
    }

    protected function getComposerPackageName(): string
    {
        return self::PACKAGE_NAME;
    }
}
