<?php
/**
 * PimcoreConfigurationBundle
 * Copyright (c) Lukaschel
 */

namespace Lukaschel\PimcoreConfigurationBundle\Tool;

use Doctrine\DBAL\Migrations\AbortMigrationException;
use Lukaschel\PimcoreConfigurationBundle\Configuration\Configuration;
use Lukaschel\PimcoreConfigurationBundle\PimcoreConfigurationBundle;
use PackageVersions\Versions;
use Pimcore\Bundle\AdminBundle\Security\User\TokenStorageUserResolver;
use Pimcore\Extension\Bundle\Installer\AbstractInstaller;
use Pimcore\Model\Translation;
use Pimcore\Tool\Admin;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Yaml\Yaml;

class Install extends AbstractInstaller
{
    /**
     * @var Configuration
     */
    protected $configuration;
    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * @var string
     */
    private $currentVersion;

    /**
     * Install constructor.
     *
     * @param TokenStorageUserResolver $resolver
     * @param DecoderInterface         $serializer
     */
    public function __construct(TokenStorageUserResolver $resolver, DecoderInterface $serializer)
    {
        parent::__construct();

        $this->fileSystem = new Filesystem();
        try {
            $this->currentVersion = Versions::getVersion(PimcoreConfigurationBundle::PACKAGE_NAME);
        } catch (\OutOfBoundsException $e) {
            $bundle = new PimcoreConfigurationBundle();
            $this->currentVersion = $bundle->getVersion();
        }
    }

    /**
     * @param Configuration $configuration
     */
    public function setConfiguration(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @throws AbortMigrationException
     *
     * @return bool|void
     */
    public function install()
    {
        $this->installOrUpdateConfigFile();
        $this->installTranslations();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function update()
    {
        $this->installOrUpdateConfigFile();
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall()
    {
        if ($this->fileSystem->exists(Configuration::SYSTEM_CONFIG_FILE_PATH)) {
            $this->fileSystem->remove(
                Configuration::SYSTEM_CONFIG_DIR_PATH
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isInstalled()
    {
        return $this->fileSystem->exists(Configuration::SYSTEM_CONFIG_FILE_PATH);
    }

    /**
     * {@inheritdoc}
     */
    public function canBeInstalled()
    {
        return !$this->fileSystem->exists(Configuration::SYSTEM_CONFIG_FILE_PATH);
    }

    /**
     * {@inheritdoc}
     */
    public function canBeUninstalled()
    {
        return $this->fileSystem->exists(Configuration::SYSTEM_CONFIG_FILE_PATH);
    }

    /**
     * {@inheritdoc}
     */
    public function needsReloadAfterInstall()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function canBeUpdated()
    {
        $needUpdate = false;
        if ($this->fileSystem->exists(Configuration::SYSTEM_CONFIG_FILE_PATH)) {
            $config = Yaml::parse(file_get_contents(Configuration::SYSTEM_CONFIG_FILE_PATH));
            if ($config['version'] !== $this->currentVersion) {
                $needUpdate = true;
            }
        }

        return $needUpdate;
    }

    /**
     * @return string
     */
    protected function getInstallSourcesPath()
    {
        return __DIR__ . '/../Resources/install';
    }

    /**
     * install / update config file.
     */
    private function installOrUpdateConfigFile()
    {
        if (!$this->fileSystem->exists(Configuration::SYSTEM_CONFIG_DIR_PATH)) {
            $this->fileSystem->mkdir(Configuration::SYSTEM_CONFIG_DIR_PATH);
        }

        $config = ['version' => $this->currentVersion];
        $yml = Yaml::dump($config);
        file_put_contents(Configuration::SYSTEM_CONFIG_FILE_PATH, $yml);
    }

    /**
     * @throws AbortMigrationException
     */
    private function installTranslations()
    {
        $csv = $this->getInstallSourcesPath() . '/translations/backend.csv';

        try {
            Translation\Website::importTranslationsFromFile($csv, true, Admin::getLanguages());
        } catch (\Exception $e) {
            throw new AbortMigrationException(sprintf('Failed to install admin translations. error was: "%s"', $e->getMessage()));
        }
    }
}
