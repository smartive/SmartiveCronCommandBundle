<?php

namespace Smartive\CronCommandBundle\Command;

use Jobby\Jobby;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Command to execute a list of cron jobs
 */
class CronJobCommand extends Command
{
    /**
     * @var KernelInterface
     */
    private $container;

    /**
     * @var Jobby
     */
    private $jobby;

    /**
     * @var array
     */
    private $jobs;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container Container instance
     * @param Jobby              $jobby     Jobby instance
     * @param array              $jobs      List of jobs to execute
     */
    public function __construct(ContainerInterface $container, Jobby $jobby, array $jobs)
    {
        parent::__construct();

        $this->container = $container;
        $this->jobby = $jobby;
        $this->jobs = $jobs;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('smartive:cron-command:run')
            ->setDescription('Executes all commands that are listed to be executed as cron jobs.');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $kernel = $this->container->get('kernel');

        foreach ($this->jobs as $job) {

            /** @var Command $cmd */
            $cmd = $this->container->get($job['command_service_id']);
            $cmdName = $cmd->getName();
            $application = new Application($kernel);
            $application->setAutoExit(false);

            $inputArgs = new ArrayInput(array_merge(
                [ 'command' => $cmdName ],
                $job['command_arguments']
            ));

            $jobbyJobArguments = array_merge([
                'closure' => function () use ($application, $inputArgs, $cmdName) {
                    $output = new BufferedOutput();
                    $output->writeln('Running ' . $cmdName);
                    if (0 === $application->run($inputArgs, $output)) {
                        $output->writeln($cmdName . ' ran successfully!');
                        echo $output->fetch();

                        return true;
                    }

                    return $output->fetch();
                },
                'enabled' => true,
            ], $job['attributes']);

            $this->jobby->add('jobby:' . $cmdName . ':' . md5(serialize($job)), $jobbyJobArguments);
        }
        $output->writeln('Start running cron commands..');
        $this->jobby->run();

        return 0;
    }
}
