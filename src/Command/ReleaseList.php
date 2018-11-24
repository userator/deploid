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
class ReleaseList extends Command {

	protected function configure() {
		$this->setName('release:list');
		$this->setDescription('List releases');
		$this->setHelp('This command show list releases');
		$this->addArgument('path', InputArgument::OPTIONAL, 'path to target directory', getcwd());
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$path = $this->getApplication()->absolutePath($input->getArgument('path'), getcwd());
		
		$payload = $this->getApplication()->deploidReleaseList($path);
		$output->writeln($payload->getMessage());
		return $payload->getCode();
	}

}