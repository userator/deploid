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
class ReleaseRotate extends Command {

	protected function configure() {
		$this->setName('release:rotate');
		$this->setDescription('Rotate release directory');
		$this->setHelp('This command rotate release directory');
		$this->addArgument('quantity', InputArgument::REQUIRED, 'quantity leave releases directory');
		$this->addArgument('path', InputArgument::OPTIONAL, 'path to target directory', getcwd());
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$payload = $this->getApplication()->deploidStructureValidate($input->getArgument('path'));
		if ($payload->getType() == Payload::STRUCTURE_VALIDATE_FAIL) {
			$output->writeln($payload->getMessage());
			return $payload->getCode();
		}

		$payload = $this->getApplication()->deploidReleaseRotate($input->getArgument('quantity'), $input->getArgument('path'));
		if ($payload->getType() == Payload::RELEASE_ROTATE_FAIL) {
			$output->writeln($payload->getMessage());
			return $payload->getCode();
		}

		$output->writeln($payload->getMessage());

		return $payload->getCode();
	}

}