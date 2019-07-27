<?php

namespace Deploid\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * @method \Deploid\Application getApplication() return application object
 */
class ReleaseRemove extends Command
{

    protected function configure()
    {
        $this->setName('release:remove');
        $this->setDescription('Remove release directory');
        $this->setHelp('This command remove a release directory');
        $this->addArgument('release', InputArgument::REQUIRED, 'release name');
        $this->addArgument('path', InputArgument::OPTIONAL, 'path to target directory', getcwd());
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $this->getApplication()->absolutePath($input->getArgument('path'), getcwd());

        $payload = $this->getApplication()->deploidReleaseRemove($input->getArgument('release'), $path);
        $output->writeln($payload->getMessage());
        return $payload->getCode();
    }

}
