<?php
/**
 * PimcoreConfigurationBundle
 * Copyright (c) Lukaschel
 */

declare(strict_types=1);

namespace Lukaschel\PimcoreConfigurationBundle\Configuration;

class Configuration
{
    public const SYSTEM_CONFIG_DIR_PATH = PIMCORE_PRIVATE_VAR . '/bundles/PimcoreConfigurationBundle';
    public const SYSTEM_CONFIG_FILE_PATH = PIMCORE_PRIVATE_VAR . '/bundles/PimcoreConfigurationBundle/config.yml';
    public const BUNDLES_CONFIG_FILE_PATH = PIMCORE_PRIVATE_VAR . '/bundles/PimcoreConfigurationBundle/bundles';

    protected array $config = [];

    /**
     * @param array $config
     */
    public function setConfig(array $config = []): void
    {
        $this->config = $config;
    }

    public function getConfigArray(): array
    {
        return $this->config;
    }

    public function getConfig(string $slot)
    {
        return $this->config[$slot];
    }
}
