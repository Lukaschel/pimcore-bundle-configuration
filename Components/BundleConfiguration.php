<?php
/**
 * PimcoreConfigurationBundle
 * Copyright (c) Lukaschel
 */

declare(strict_types=1);

namespace Lukaschel\PimcoreConfigurationBundle\Components;

use Lukaschel\PimcoreConfigurationBundle\Configuration\Configuration;
use Lukaschel\PimcoreConfigurationBundle\Controller\AdminBundleConfigController;
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

    private ContainerInterface $container;
    private RequestStack $request_stack;
    private DocumentResolver $document_resolver;
    private array $config = [];

    public function __construct(
        RequestStack $request_stack,
        DocumentResolver $document_resolver
    ) {
        $this->request_stack = $request_stack;
        $this->document_resolver = $document_resolver;
    }

    /**
     * @return array|false|void
     */
    public function getConfig(
        $key,
        string $siteRootId = '',
        string $language = '',
        string $bundleName = ''
    ) {
        // get current language
        $validLanguages = Tool::getValidLanguages();

        if (empty($language)) {
            if ($this->request_stack->getCurrentRequest() !== null) {
                $language = $this->request_stack->getCurrentRequest()->getLocale();
            } else if (count($validLanguages) > 0) {
                $language = $validLanguages[0];
            }
        }

        if (!in_array($language, $validLanguages)) {
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
            $siteRootId = (string) $site->getRootId();
        }

        // getting bundle
        if ($bundleName) {
            $bundle = $this->getBundle($bundleName);
        } else {
            $bundle = $this->getBundle($this->document_resolver->getDocument()->getModule());
        }

        $configData = null;

        // get bundle default config
        [$fileExists, $filePath] = AdminBundleConfigController::checkYamlFileExists($bundle->getPath() . '/Resources/config/bundle/bundle');
        if ($fileExists === true) {
            $this->config = Yaml::parseFile($filePath);
        }

        [$fileExists, $filePath] = AdminBundleConfigController::checkYamlFileExists(Configuration::BUNDLES_CONFIG_FILE_PATH . '/' . $bundle->getName() . '/' . $siteRootId . '_' . $language);
        if ($fileExists === true) {
            $configData = Yaml::parseFile($filePath);
        }

        if (is_array($configData)) {
            if (count($this->config)) {
                $this->config = array_replace_recursive($this->config, $configData);
            } else {
                $this->config = $configData;
            }
        }

        return $this->array_search_key($key, $this->config);
    }

    /**
     * @return false|mixed
     */
    private function array_search_key($needle, array $array)
    {
        foreach ($array as $key => $value) {
            if ($key === $needle) {
                return $value;
            }
            if (is_array($value) && ($result = $this->array_search_key($needle, $value)) !== false) {
                return $result;
            }
        }

        return false;
    }
}
