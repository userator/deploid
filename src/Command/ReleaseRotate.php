<?php

namespace Deploid\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * @method \Deploid\Application getApplication() return application object
 */
class ReleaseRotate extends Command
{

    protected function configure()
    {
        $this->setName('release:rotate');
        $this->setDescription('Rotate release directory');
        $this->setHelp('This command rotate release directory');
        $this->addArgument('quantity', InputArgument::REQUIRED, 'quantity leave releases directory');
        $this->addArgument('path', InputArgument::OPTIONAL, 'path to target directory', getcwd());
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $this->getApplication()->absolutePath($input->getArgument('path'), getcwd());

        $payload = $this->getApplication()->deploidReleaseRotate($input->getArgument('quantity'), $path);
        $output->writeln($payload->getMessage());
        return $payload->getCode();
    }

}
