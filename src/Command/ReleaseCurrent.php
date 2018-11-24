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
class ReleaseCurrent extends Command {

	protected function configure() {
		$this->setName('release:current');
		$this->setDescription('Sets the current release');
		$this->setHelp('This command sets the current release');
		$this->addArgument('path', InputArgument::OPTIONAL, 'path to target directory', getcwd());
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$path = $this->getApplication()->absolutePath($input->getArgument('path'), getcwd());
		
		$payload = $this->getApplication()->deploidReleaseCurrent($path);
		$output->writeln($payload->getMessage());
		return $payload->getCode();
	}

}