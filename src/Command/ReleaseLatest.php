<?php

namespace Deploid\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * @method \Deploid\Application getApplication() return application object
 */
class ReleaseLatest extends Command
{

    protected function configure()
    {
        $this->setName('release:latest');
        $this->setDescription('Latest release');
        $this->setHelp('This command show latest release');
        $this->addArgument('path', InputArgument::OPTIONAL, 'path to target directory', getcwd());
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $this->getApplication()->absolutePath($input->getArgument('path'), getcwd());

        $payload = $this->getApplication()->deploidReleaseLatest($path);
        $output->writeln($payload->getMessage());
        return $payload->getCode();
    }

}
