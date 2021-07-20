<?php
/**
 * PimcoreConfigurationBundle
 * Copyright (c) Lukaschel
 */

declare(strict_types=1);

namespace Lukaschel\PimcoreConfigurationBundle\Tools;

use Lukaschel\PimcoreConfigurationBundle\Configuration\Configuration;
use Lukaschel\PimcoreConfigurationBundle\PimcoreConfigurationBundle;
use PackageVersions\Versions;
use Pimcore\Extension\Bundle\Installer\AbstractInstaller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\Yaml\Yaml;

class Installer extends AbstractInstaller
{
    protected Configuration $configuration;
    private Filesystem $fileSystem;
    private string $currentVersion;
    protected BundleInterface $bundle;

    public function __construct(
        BundleInterface $bundle
    ) {
        $this->bundle = $bundle;
        $this->fileSystem = new Filesystem();

        try {
            $this->currentVersion = Versions::getVersion(PimcoreConfigurationBundle::PACKAGE_NAME);
        } catch (\OutOfBoundsException $e) {
            $this->currentVersion = $this->bundle->getVersion();
        }

        parent::__construct();
    }

    public function setConfiguration(Configuration $configuration): void
    {
        $this->configuration = $configuration;
    }

    public function install(): void
    {
        $this->installOrUpdateConfigFile();
    }

    public function update(): void
    {
        $this->installOrUpdateConfigFile();
    }

    public function uninstall(): void
    {
        if ($this->fileSystem->exists(Configuration::SYSTEM_CONFIG_FILE_PATH)) {
            $this->fileSystem->remove(
                Configuration::SYSTEM_CONFIG_DIR_PATH
            );
        }
    }

    public function isInstalled(): bool
    {
        return $this->fileSystem->exists(Configuration::SYSTEM_CONFIG_FILE_PATH);
    }

    public function canBeInstalled(): bool
    {
        return !$this->fileSystem->exists(Configuration::SYSTEM_CONFIG_FILE_PATH);
    }

    public function canBeUninstalled(): bool
    {
        return $this->fileSystem->exists(Configuration::SYSTEM_CONFIG_FILE_PATH);
    }

    public function needsReloadAfterInstall(): bool
    {
        return true;
    }

    public function canBeUpdated(): bool
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

    private function installOrUpdateConfigFile(): void
    {
        if (!$this->fileSystem->exists(Configuration::SYSTEM_CONFIG_DIR_PATH)) {
            $this->fileSystem->mkdir(Configuration::SYSTEM_CONFIG_DIR_PATH);
        }

        $config = ['version' => $this->currentVersion];
        $yml = Yaml::dump($config);
        file_put_contents(Configuration::SYSTEM_CONFIG_FILE_PATH, $yml);
    }
}
