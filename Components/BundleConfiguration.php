<?php
/**
 * PimcoreConfigurationBundle
 * Copyright (c) Lukaschel
 */

namespace Lukaschel\PimcoreConfigurationBundle\Components;

use Lukaschel\PimcoreConfigurationBundle\Configuration\Configuration;
use Lukaschel\PimcoreConfigurationBundle\Traits\ConfigurationTrait;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Model\Site;
use Pimcore\Tool;
use Pimcore\Tool\Frontend;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Yaml\Yaml;

class BundleConfiguration
{
    use ConfigurationTrait;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var RequestStack
     */
    private $request_stack;

    /**
     * @var DocumentResolver
     */
    private $document_resolver;

    /**
     * @var array
     */
    private $config = [];

    /**
     * BundleConfiguration constructor.
     *
     * @param ContainerInterface $container
     * @param RequestStack       $request_stack
     * @param DocumentResolver   $document_resolver
     */
    public function __construct(
        ContainerInterface $container,
        RequestStack $request_stack,
        DocumentResolver $document_resolver
    ) {
        $this->container = $container;
        $this->request_stack = $request_stack;
        $this->document_resolver = $document_resolver;
    }

    /**
     * @param $key
     * @param string $siteRootId
     * @param string $language
     * @param string $bundleName
     *
     * @return array|void
     */
    public function getConfig(
        $key,
        $siteRootId = '',
        $language = '',
        $bundleName = ''
    ) {
        // get current language
        if (!$language) {
            $language = $this->request_stack->getCurrentRequest()->getLocale();
        }

        $languages = Tool::getValidLanguages();

        if (!in_array($language, $languages)) {
            return;
        }

        // getting siteRootId
        if ($siteRootId) {
            $site = Site::getByRootId($siteRootId);
        } else {
            $site = Frontend::getSiteForDocument($this->document_resolver->getDocument());
            if (!$site instanceof Site) {
                $siteRootId = 'root';
            }
        }

        if ($site instanceof Site) {
            $siteRootId = $site->getRootId();
        }

        // getting bundle
        if ($bundleName) {
            $bundle = $this->getBundle($bundleName, $this->container);
        } else {
            $bundle = $this->getBundle($this->document_resolver->getDocument()->getModule(), $this->container);
        }

        // get bundle default config
        if (file_exists($bundle->getPath() . '/Resources/config/bundle/bundle.yml')) {
            $this->config = Yaml::parseFile($bundle->getPath() . '/Resources/config/bundle/bundle.yml');
        }

        if (file_exists(Configuration::BUNDLES_CONFIG_FILE_PATH . '/' . $bundle->getName() . '/' . $siteRootId . '_' . $language . '.yml')) {
            $configData = Yaml::parseFile(Configuration::BUNDLES_CONFIG_FILE_PATH . '/' . $bundle->getName() . '/' . $siteRootId . '_' . $language . '.yml');
        }

        if (sizeof($this->config) and is_array($configData)) {
            $this->config = array_replace_recursive($this->config, $configData);
        } elseif (is_array($configData)) {
            $this->config = $configData;
        }

        return $this->array_search_key($key, $this->config);
    }

    /**
     * @param string $needle
     * @param array  $array
     *
     * @return array|void
     */
    private function array_search_key($needle, $array)
    {
        foreach ($array as $key => $value) {
            if ($key == $needle) {
                return $value;
            }
            if (is_array($value)) {
                if (($result = $this->array_search_key($needle, $value)) !== false) {
                    return $result;
                }
            }
        }
    }
}
