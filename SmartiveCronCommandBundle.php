<?php

namespace Smartive\CronCommandBundle;

use Smartive\CronCommandBundle\DependencyInjection\Compiler\CronCommandPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * SmartiveCronCommandBundle
 */
class SmartiveCronCommandBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new CronCommandPass());
    }
}
