<?php

namespace Deploid\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class Remove extends Command {

	protected function configure() {
		$this->setName('deploid:remove');
		$this->setDescription('Remove release directory');
		$this->setHelp('This command remove a release directory');
		$this->addArgument('release', InputArgument::REQUIRED, 'release directory name');
		$this->addArgument('path', InputArgument::REQUIRED, 'path to workdir');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$proccess = new Process([
			'rm ' . rtrim($input->getArgument('path'), '/') . '/' . $input->getArgument('release'),
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