<?php

namespace Smartive\CronCommandBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('smartive_cron_command');
        $rootNode
            ->children()
                ->arrayNode('jobby_options')
                    ->defaultValue([
                        'output' => '%kernel.logs_dir%/cron-command.log',
                        'debug' => '%kernel.debug%',
                    ])
                    ->beforeNormalization()
                        ->ifArray()
                        ->then(function ($v) {
                            if (!isset($v['output'])) $v['output'] = '%kernel.logs_dir%/jobby.log';
                            if (!isset($v['debug'])) $v['debug'] = '%kernel.debug%';
                            return $v;
                        })
                    ->end()
                    ->prototype('scalar')->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
