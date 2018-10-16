<?php

namespace Deploid\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * @method \Deploid\Application getApplication() return application object
 */
class ReleaseCurrent extends Command {

	protected function configure() {
		$this->setName('release:current');
		$this->setDescription('Sets the current release');
		$this->setHelp('This command sets the current release');
		$this->addArgument('release', InputArgument::REQUIRED, 'release name');
		$this->addArgument('path', InputArgument::OPTIONAL, 'structure path', getcwd());
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$payload = $this->getApplication()->deploidStructureValidate($input->getArgument('release'), $input->getArgument('path'));
		if ($payload->getType() == Payload::STRUCTURE_VALIDATE_FAIL) {
			$output->writeln($payload->getMessage());
			return $payload->getCode();
		}

		$payload = $this->getApplication()->deploidReleaseCurrent($input->getArgument('release'), $input->getArgument('path'));
		if ($payload->getType() == Payload::RELEASE_CURRENT_FAIL) {
			$output->writeln($payload->getMessage());
			return $payload->getCode();
		}

		$output->writeln($payload->getMessage());

		return $payload->getCode();
	}

}