<?php
/**
 * PimcoreConfigurationBundle
 * Copyright (c) Lukaschel
 */

namespace Lukaschel\PimcoreConfigurationBundle;

use Lukaschel\PimcoreConfigurationBundle\Tool\Install;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Pimcore\HttpKernel\Bundle\DependentBundleInterface;
use Pimcore\HttpKernel\BundleCollection\BundleCollection;

class PimcoreConfigurationBundle extends AbstractPimcoreBundle implements DependentBundleInterface
{
    use PackageVersionTrait;

    const PACKAGE_NAME = 'lukaschel/pimcore-bundle-configuration';

    /**
     * @return string
     */
    public function getVersion()
    {
        return '1.0.1';
    }

    /**
     * @param BundleCollection $collection
     */
    public static function registerDependentBundles(BundleCollection $collection)
    {
    }

    /**
     * @return array|\Pimcore\Routing\RouteReferenceInterface[]|string[]
     */
    public function getJsPaths()
    {
        return [
            // '/bundles/pimcoreconfiguration/js/pimcore/startup.js'
        ];
    }

    /**
     * @return mixed
     */
    public function getInstaller()
    {
        return $this->container->get(Install::class);
    }

    /**
     * @return string
     */
    protected function getComposerPackageName(): string
    {
        return self::PACKAGE_NAME;
    }
}
