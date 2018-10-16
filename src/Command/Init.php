<?php

namespace Deploid\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Process\Process;

class Init extends Command {

	protected function configure() {
		$this->setName('deploid:init');
		$this->setDescription('Initialize new directory structure');
		$this->setHelp('This command initialize a directory structure in the specified path');
		$this->addArgument('path', InputArgument::OPTIONAL, 'path to target directory', getcwd());
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$proccess = new Process([
			'mkdir -rf ' . rtrim($input->getArgument('path'), '/') . '/releases',
			'mkdir -rf ' . rtrim($input->getArgument('path'), '/') . '/shared',
			'touch ' . rtrim($input->getArgument('path'), '/') . '/deploid.log',
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