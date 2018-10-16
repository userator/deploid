<?php

namespace Deploid\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Deploid\Payload;
class Create extends Command {

	protected function configure() {
		$this->setName('deploid:create');
		$this->setDescription('Creates new release directory');
		$this->setHelp('This command creates a release directory');
		$this->addArgument('release', InputArgument::OPTIONAL, 'release directory name', date('Y-m-d H:i:s'));
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$payload = $this->getApplication()->deploidStructureValidate(getcwd());
		if ($payload->getType() != Payload::STRUCTURE_VALID) {
			$output->writeln($payload->getMessage());
//			$output->writeln('directory structure is invalid');
			return 255;
		}

		$payload = $this->getApplication()->deploidReleaseCreate($input->getArgument('release'));
		if ($payload->getType() != Payload::RELEASE_CREATED) {
			$output->writeln($payload->getMessage());
//			$output->writeln('directory structure is invalid');
			return 255;
		}
		
		$proccess = new Process([
			'mkdir ' . getcwd() . '/' . $input->getArgument('release'),
		]);

		$proccess->run();

		if (!$proccess->isSuccessful()) {
			$output->writeln($proccess->getErrorOutput());
			return $proccess->getExitCode();
		}

		$output->writeln('success');

		return 0;
	}

}