<?php
/**
 * PimcoreConfigurationBundle
 * Copyright (c) Lukaschel
 */

namespace Lukaschel\PimcoreConfigurationBundle\Controller\Admin;

use Lukaschel\PimcoreConfigurationBundle\Configuration\Configuration;
use Lukaschel\PimcoreConfigurationBundle\Controller\AbstractController;
use Lukaschel\PimcoreConfigurationBundle\Traits\ConfigurationTrait;
use Pimcore\Model\Site;
use Pimcore\Tool;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Yaml\Yaml;

/**
 * @Route("/admin/pimcoreconfiguration")
 */
class BundleConfigController extends AbstractController
{
    use ConfigurationTrait;

    /**
     * @param $bundleName
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/bundle/{bundleName}", name="pimcoreconfiguration_index")
     */
    public function indexAction($bundleName)
    {
        // getting bundle
        $bundle = $this->getBundle($bundleName, $this->container);

        // creating bundle default config yml
        if (!is_dir($bundle->getPath() . '/Resources/config/bundle')) {
            mkdir($bundle->getPath() . '/Resources/config/bundle', 0755, true);
            file_put_contents($bundle->getPath() . '/Resources/config/bundle/bundle.yml', '');
        }

        // creating bundle config dir
        if (!is_dir(Configuration::BUNDLES_CONFIG_FILE_PATH . '/' . $bundle->getName())) {
            mkdir(Configuration::BUNDLES_CONFIG_FILE_PATH . '/' . $bundle->getName() . '/', 0755, true);
        }

        return $this->renderTemplate('admin/config-index.html.twig', [
            'bundleName' => $bundle->getName(),
            'sites' => $this->getAllAvailableSites(),
            'languages' => Tool::getValidLanguages(),
        ]);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @Route("/getConfig", name="pimcoreconfiguration_getconfig")
     */
    public function getConfig(Request $request)
    {
        $config = [];
        $siteRootId = '';

        // bundle
        $bundle = $this->getBundle($request->request->get('bundleName'), $this->container);

        // language
        $languages = Tool::getValidLanguages();
        $language = $languages[$request->request->get('language')];

        // site
        $site = $request->request->get('site');

        switch ($site) {
            case 'default':
                $config['site'] = $site;
                $language = implode('/', $languages);
                break;

            case 'root':
                $config['site'] = $site;
                $siteRootId = $site;
                break;

            default:
                try {
                    /** @var Site $site */
                    $site = Site::getById((int) $site);
                    $config['site'] = $site->getMainDomain();
                    $siteRootId = $site->getRootId();
                } catch (\Exception $e) {
                    throw new \RuntimeException('Site is not available');
                }
                break;
        }

        // get default yml
        if (file_exists($bundle->getPath() . '/Resources/config/bundle/bundle.yml')) {
            $config['config'] = Yaml::parseFile($bundle->getPath() . '/Resources/config/bundle/bundle.yml');
        }

        if ($siteRootId) {
            // get yml config
            if (file_exists(Configuration::BUNDLES_CONFIG_FILE_PATH . '/' . $bundle->getName() . '/' . $siteRootId . '_' . $language . '.yml')) {
                $configSite = Yaml::parseFile(Configuration::BUNDLES_CONFIG_FILE_PATH . '/' . $bundle->getName() . '/' . $siteRootId . '_' . $language . '.yml');

                if ($config['config'] and is_array($configSite)) {
                    $config['config'] = array_replace_recursive($config['config'], $configSite);
                } elseif (is_array($configSite)) {
                    $config['config'] = $configSite;
                }
            }
        }

        return new JsonResponse(array_merge($config, [
            'language' => $language,
        ]));
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @Route("/saveConfig", name="pimcoreconfiguration_saveconfig")
     */
    public function saveData(Request $request)
    {
        try {
            $bundle = $this->getBundle($request->request->get('bundleName'), $this->container);
            if (!$request->request->get('site') ||
                !is_numeric($request->request->get('language')) ||
                !is_array(json_decode($request->request->get('config'), true))) {
                throw new \RuntimeException('Invalid parameters');
            }
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false]);
        }

        $configDefault = [];

        // language
        $languages = Tool::getValidLanguages();
        $language = $languages[$request->request->get('language')];

        // site
        $site = $request->request->get('site');

        // get config
        $configSave = json_decode($request->request->get('config'), true);

        switch ($site) {
            case 'default':
                // save default data
                if (file_exists($bundle->getPath() . '/Resources/config/bundle/bundle.yml')) {
                    file_put_contents($bundle->getPath() . '/Resources/config/bundle/bundle.yml', Yaml::dump($configSave));
                }

                return new JsonResponse(['success' => true]);
                break;

            case 'root':
                $siteRootId = $site;
                break;

            default:
                try {
                    /** @var Site $site */
                    $site = Site::getById((int) $site);
                    $siteRootId = $site->getRootId();
                } catch (\Exception $e) {
                    throw new \RuntimeException('Site is not available');
                }
                break;
        }

        if ($siteRootId) {
            // getting default config
            if (file_exists($bundle->getPath() . '/Resources/config/bundle/bundle.yml')) {
                $configDefault = Yaml::parseFile($bundle->getPath() . '/Resources/config/bundle/bundle.yml');
            }

            // compare with default data
            if (sizeof($configDefault)) {
                $configSave = $this->compare($configSave, $configDefault);
            }

            // save config
            file_put_contents(Configuration::BUNDLES_CONFIG_FILE_PATH . '/' . $bundle->getName() . '/' . $siteRootId . '_' . $language . '.yml', Yaml::dump($configSave));
        }

        return new JsonResponse(['success' => true]);
    }

    /**
     * Returns an array with the differences between $array1 and $array2
     *
     * @param array $array1
     * @param array $array2
     *
     * @return array
     */
    private function compare($array1, $array2)
    {
        $result = [];
        foreach ($array1 as $key => $value) {
            if (!is_array($array2) || !array_key_exists($key, $array2)) {
                $result[$key] = $value;
                continue;
            }
            if (is_array($value)) {
                $recursiveArrayDiff = static::compare($value, $array2[$key]);
                if (count($recursiveArrayDiff)) {
                    $result[$key] = $recursiveArrayDiff;
                }
                continue;
            }
            if ($value != $array2[$key]) {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
