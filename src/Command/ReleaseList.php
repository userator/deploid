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
		$payload = $this->getApplication()->deploidStructureValidate($input->getArgument('path'));
		if ($payload->getType() == Payload::STRUCTURE_VALIDATE_FAIL) {
			$output->writeln($payload->getMessage());
			return $payload->getCode();
		}

		$payload = $this->getApplication()->deploidReleaseList($input->getArgument('path'));
		if ($payload->getType() == Payload::RELEASE_LIST_FAIL) {
			$output->writeln($payload->getMessage());
			return $payload->getCode();
		}

		$output->writeln($payload->getMessage());

		return $payload->getCode();
	}

}