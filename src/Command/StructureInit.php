<?php

namespace Deploid\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * @method \Deploid\Application getApplication() return application object
 */
class StructureInit extends Command {

	protected function configure() {
		$this->setName('structure:init');
		$this->setDescription('Initialize new directory structure');
		$this->setHelp('This command initialize a directory structure in the specified path');
		$this->addArgument('path', InputArgument::OPTIONAL, 'path to target directory', getcwd());
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$path = $this->getApplication()->absolutePath($input->getArgument('path'), getcwd());

		if ($this->getApplication()->isStructureInitialized($path)) {
			$payload = $this->getApplication()->deploidStructureClean($path);
			$output->writeln($payload->getMessage());
			return $payload->getCode();
		} else {
			$payload = $this->getApplication()->deploidStructureInit($path);
			$output->writeln($payload->getMessage());
			return $payload->getCode();
		}
	}

}