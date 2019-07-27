<?php

namespace Deploid\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * @method \Deploid\Application getApplication() return application object
 */
class ReleaseSetup extends Command
{

    protected function configure()
    {
        $this->setName('release:setup');
        $this->setDescription('Setup the current release');
        $this->setHelp('This command setup the current release');
        $this->addArgument('release', InputArgument::REQUIRED, 'release name');
        $this->addArgument('path', InputArgument::OPTIONAL, 'path to target directory', getcwd());
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $this->getApplication()->absolutePath($input->getArgument('path'), getcwd());

        $payload = $this->getApplication()->deploidReleaseSetup($input->getArgument('release'), $path);
        $output->writeln($payload->getMessage());
        return $payload->getCode();
    }

}
