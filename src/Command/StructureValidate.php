<?php

namespace Deploid\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * @method \Deploid\Application getApplication() return application object
 */
class StructureValidate extends Command {

	protected function configure() {
		$this->setName('structure:validate');
		$this->setDescription('Validate existing directory structure');
		$this->setHelp('This command validate existing directory structure in the specified path');
		$this->addArgument('path', InputArgument::OPTIONAL, 'path to target directory', getcwd());
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$payload = $this->getApplication()->deploidStructureValidate($input->getArgument('path'));
		$output->writeln($payload->getMessage());
		return $payload->getCode();
	}

}