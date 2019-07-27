<?php

namespace Deploid\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Deploid\Payload;

/**
 * @method \Deploid\Application getApplication() return application object
 */
class ReleaseCreate extends Command
{

    protected function configure()
    {
        $this->setName('release:create');
        $this->setDescription('Creates new release directory');
        $this->setHelp('This command creates a release directory');
        $this->addArgument('path', InputArgument::OPTIONAL, 'path to target directory', getcwd());
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $this->getApplication()->absolutePath($input->getArgument('path'), getcwd());
        $releaseName = date($this->getApplication()->getReleaseNameFormat());

        $payload = $this->getApplication()->deploidReleaseCreate($releaseName, $path);
        $output->writeln($payload->getMessage());
        return $payload->getCode();
    }

}
