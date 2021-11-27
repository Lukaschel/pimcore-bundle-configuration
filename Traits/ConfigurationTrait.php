<?php
/**
 * PimcoreConfigurationBundle
 * Copyright (c) Lukaschel
 */

declare(strict_types=1);

namespace Lukaschel\PimcoreConfigurationBundle\Traits;

use Pimcore\Kernel;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Site;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

trait ConfigurationTrait
{
    public function getAllAvailableSites(): array
    {
        $sitesList = new Site\Listing();
        $sitesObjects = $sitesList->load();
        $sites = [
            [
                'id' => 'default',
                'domain' => 'default',
            ],
            [
                'id' => 'root',
                'rootId' => 1,
                'domains' => '',
                'rootPath' => '/',
                'domain' => 'root-site',
            ],
        ];

        foreach ($sitesObjects as $site) {
            if (($site->getRootDocument() instanceof Page) && $site->getMainDomain()) {
                $sites[] = [
                    'id' => $site->getId(),
                    'rootId' => $site->getRootId(),
                    'domains' => implode(',', $site->getDomains()),
                    'rootPath' => $site->getRootPath(),
                    'domain' => $site->getMainDomain(),
                ];
            }
        }

        return $sites;
    }

    private function getBundle(string $bundleName): BundleInterface
    {
        try {
            /** @var Kernel $kernel */
            $kernel = \Pimcore::getKernel();
            $bundle = $kernel->getBundle($bundleName);
        } catch (\Exception $e) {
            throw new \RuntimeException('Invalid bundle name: ' . $bundleName);
        }

        return $bundle;
    }
}
