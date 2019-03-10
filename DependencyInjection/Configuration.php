<?php
/**
 * PimcoreConfigurationBundle
 * Copyright (c) Lukaschel
 */

namespace Lukaschel\PimcoreConfigurationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('pimcore_configuration');
        $rootNode
            ->children()
                ->scalarNode('storage_path')->cannotBeEmpty()->defaultValue('/PimcoreConfiguration')->end()
                ->arrayNode('data_object_save_handler')
                    ->children()
                        ->booleanNode('enabled')->defaultFalse()->end()
                        ->arrayNode('data_objects')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('key')->end()
                                    ->scalarNode('path')->end()
                                ->end()
                                ->canBeUnset()
                                ->canBeDisabled()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
