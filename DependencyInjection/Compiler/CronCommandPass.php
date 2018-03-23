<?php

namespace Smartive\CronCommandBundle\DependencyInjection\Compiler;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * CronCommandPass
 */
class CronCommandPass implements CompilerPassInterface
{
    const COMMAND_TAG = 'console.command';
    const CRON_COMMAND_TAG = 'cron.command';
    const CRON_COMMAND_ATTRIBUTE_SCHEDULE = 'schedule';
    const CRON_COMMAND_ATTRIBUTE_ARGUMENTS = 'command_arguments';

    public function process(ContainerBuilder $container)
    {
        $cronCommandServices = $container->findTaggedServiceIds(self::CRON_COMMAND_TAG);
        $jobs = [];

        foreach ($cronCommandServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $definition = $container->getDefinition($id);
                if (!$definition->hasTag(self::COMMAND_TAG)) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'The service "%s" tagged "%s" must also be tagged as "%s".',
                            $id,
                            self::CRON_COMMAND_TAG,
                            self::COMMAND_TAG
                        )
                    );
                }
                if (!$definition->isPublic()) {
                    throw new \InvalidArgumentException(
                        sprintf('The service "%s" tagged "%s" must be public.', $id, self::CRON_COMMAND_TAG)
                    );
                }
                if (empty($attributes[self::CRON_COMMAND_ATTRIBUTE_SCHEDULE])) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'The attribute "%s" must be set for service "%s" tagged "%s".',
                            self::CRON_COMMAND_ATTRIBUTE_SCHEDULE,
                            $id,
                            self::CRON_COMMAND_TAG
                        )
                    );
                }

                $commandArguments = [];
                if (isset($attributes[self::CRON_COMMAND_ATTRIBUTE_ARGUMENTS])) {
                    $commandArgumentsOption = $attributes[self::CRON_COMMAND_ATTRIBUTE_ARGUMENTS];
                    if (is_array($commandArgumentsOption)) {
                        $commandArguments = $commandArgumentsOption;
                    } elseif (is_string($commandArgumentsOption)) {
                        $input = new ArrayInput([]);
                        $commandArgumentsArr = explode(' ', $commandArgumentsOption);
                        foreach ($commandArgumentsArr as $commandArgument) {
                            $commandArgument = explode('=', $commandArgument);
                            if (count($commandArgument) === 2) {
                                $commandArguments[$commandArgument[0]] = $input->escapeToken($commandArgument[1]);
                            } else {
                                $commandArguments[$commandArgument[0]] = true;
                            }
                        }
                    }
                }
                $jobs[] = [
                    'command_service_id' => $id,
                    'command_arguments' => $commandArguments,
                    'attributes' => $attributes,
                ];
            }
        }

        $container->setParameter('smartive_cron_command.commands', $jobs);
    }
}
