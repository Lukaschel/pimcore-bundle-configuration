<?php
/**
 * PimcoreConfigurationBundle
 * Copyright (c) Lukaschel
 */

namespace Lukaschel\PimcoreConfigurationBundle\Traits;

use Pimcore\Model\Document\Page;
use Pimcore\Model\Site;
use Symfony\Component\DependencyInjection\ContainerInterface;

trait ConfigurationTrait
{
    /**
     * @return array
     */
    public function getAllAvailableSites()
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
            if ($site->getRootDocument() instanceof Page) {
                if ($site->getMainDomain()) {
                    $sites[] = [
                        'id' => $site->getId(),
                        'rootId' => $site->getRootId(),
                        'domains' => implode(',', $site->getDomains()),
                        'rootPath' => $site->getRootPath(),
                        'domain' => $site->getMainDomain(),
                    ];
                }
            }
        }

        return $sites;
    }

    /**
     * @param $bundleName
     *
     * @return mixed
     */
    private function getBundle($bundleName, ContainerInterface $container)
    {
        try {
            $bundle = $container->get('kernel')->getBundle($bundleName);
        } catch (\Exception $e) {
            throw new \RuntimeException('Invalid bundle name');
        }

        return $bundle;
    }
}
