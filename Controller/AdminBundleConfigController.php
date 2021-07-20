<?php
/**
 * PimcoreConfigurationBundle
 * Copyright (c) Lukaschel
 */

declare(strict_types=1);

namespace Lukaschel\PimcoreConfigurationBundle\Controller;

use Lukaschel\PimcoreConfigurationBundle\Configuration\Configuration;
use Lukaschel\PimcoreConfigurationBundle\Traits\ConfigurationTrait;
use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Model\Site;
use Pimcore\Tool;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Yaml\Yaml;

/**
 * @Route("/admin/pimcoreconfiguration")
 */
class AdminBundleConfigController extends AdminController
{
    use ConfigurationTrait;
    
    private const CONFIG_FILE_EXTENSIONS = ['.yaml', '.yml'];

    /**
     * @Route("/bundle/{bundleName}", name="pimcoreconfiguration_index")
     */
    public function indexAction($bundleName): Response
    {
        $bundle = $this->getBundle($bundleName);

        // creating bundle default config yml - if not exists
        if (!is_dir($bundle->getPath() . '/Resources/config/bundle')) {
            mkdir($bundle->getPath() . '/Resources/config/bundle', 0755, true);
        }
        [$fileExists, $filePath] = self::checkYamlFileExists($bundle->getPath() . '/Resources/config/bundle/bundle', true);
        if ($fileExists === false) {
            file_put_contents($filePath, '');
        }

        // creating bundle config dir
        if (!is_dir(Configuration::BUNDLES_CONFIG_FILE_PATH . '/' . $bundle->getName())) {
            mkdir(Configuration::BUNDLES_CONFIG_FILE_PATH . '/' . $bundle->getName() . '/', 0755, true);
        }

        return $this->render(
            '@PimcoreConfiguration/admin/config-index.html.twig',
            [
                'bundleName' => $bundle->getName(),
                'sites' => $this->getAllAvailableSites(),
                'languages' => Tool::getValidLanguages(),
                'text' => $this->getTranslations(),
            ]
        );
    }

    /**
     * @Route("/getConfig", name="pimcoreconfiguration_getconfig")
     */
    public function getConfig(Request $request): JsonResponse
    {
        $config = [];
        $siteRootId = '';

        $bundle = $this->getBundle($request->request->get('bundleName'));

        $languages = Tool::getValidLanguages();
        $language = $languages[$request->request->get('language')];

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

        // get default config within bundle - if provided
        [$fileExists, $filePath] = self::checkYamlFileExists($bundle->getPath() . '/Resources/config/bundle/bundle');
        if ($fileExists === true) {
            $config['config'] = Yaml::parseFile($filePath);
        }

        if ($siteRootId) {
            // get yml config
            [$fileExists, $filePath] = self::checkYamlFileExists(Configuration::BUNDLES_CONFIG_FILE_PATH . '/' . $bundle->getName() . '/' . $siteRootId . '_' . $language);
            if ($fileExists === true) {
                $configSite = Yaml::parseFile($filePath);

                if (is_array($configSite)) {
                    if ($config['config']) {
                        $config['config'] = array_replace_recursive($config['config'], $configSite);
                    } else {
                        $config['config'] = $configSite;
                    }
                }
            }
        }

        return new JsonResponse(array_merge($config, [
            'language' => $language,
        ]));
    }

    /**
     * @Route("/saveConfig", name="pimcoreconfiguration_saveconfig")
     */
    public function saveData(Request $request): JsonResponse
    {
        try {
            $bundle = $this->getBundle($request->request->get('bundleName'));
            if (!$request->request->get('site') ||
                !is_numeric($request->request->get('language')) ||
                !is_array(json_decode($request->request->get('config'), true, 512, JSON_THROW_ON_ERROR))) {
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
        $configSave = json_decode($request->request->get('config'), true, 512, JSON_THROW_ON_ERROR);

        switch ($site) {
            case 'default':
                // save default data - but be aware - could possible be lost in CI/CD Environments
                [$fileExists, $filePath] = self::checkYamlFileExists($bundle->getPath() . '/Resources/config/bundle/bundle');
                if ($fileExists === true) {
                    file_put_contents($filePath, Yaml::dump($configSave));
                }

                return new JsonResponse(['success' => true]);

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
            [$fileExists, $filePath] = self::checkYamlFileExists($bundle->getPath() . '/Resources/config/bundle/bundle');
            if ($fileExists === true) {
                $configDefault = Yaml::parseFile($filePath);
            }

            // compare with default data
            if (count($configDefault)) {
                $configSave = $this->compare($configSave, $configDefault);
            }

            // save config
            [$fileExists, $filePath] = self::checkYamlFileExists(Configuration::BUNDLES_CONFIG_FILE_PATH . '/' . $bundle->getName() . '/' . $siteRootId . '_' . $language, true);
            file_put_contents($filePath, Yaml::dump($configSave));
        }

        return new JsonResponse(['success' => true]);
    }

    private function compare(array $array1, array $array2): array
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
            if ($value !== $array2[$key]) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    public static function checkYamlFileExists(string $filename, bool $returnDefaultIfNotFound = false): array
    {
        $filename = str_replace(self::CONFIG_FILE_EXTENSIONS, '', $filename);

        foreach (self::CONFIG_FILE_EXTENSIONS as $singleExtension) {
            if (file_exists($filename . $singleExtension)) {
                return [true, $filename . $singleExtension];
            }
        }
        
        if ($returnDefaultIfNotFound === true) {
            return [false, $filename . self::CONFIG_FILE_EXTENSIONS[0]];
        }

        return [false, null];
    }

    private function getTranslations(): array
    {
        return [
            'site' => $this->trans('lukaschel_pimcoreconfigurationbundle_site'),
            'language' => $this->trans('lukaschel_pimcoreconfigurationbundle_language'),
            'save_success' => $this->trans('lukaschel_pimcoreconfigurationbundle_message_saved'),
            'save_error' => $this->trans('lukaschel_pimcoreconfigurationbundle_message_save_error'),
        ];
    }
}
