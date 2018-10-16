<?php

namespace Deploid\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class Current extends Command {

	protected function configure() {
		$this->setName('deploid:current');
		$this->setDescription('Sets the current release');
		$this->setHelp('This command sets the current release');
		$this->addArgument('release', InputArgument::REQUIRED, 'release directory name');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		if (!$this->getApplication()->deploidValidate(getcwd())) {
			$output->writeln('directory structure is invalid');
			return 255;
		}

		if (!$this->getApplication()->deploidExistRelease($input->getArgument('release'))) {
			$output->writeln('release directory "' . $input->getArgument('release') . '" not exists');
			return 255;
		}

		$proccess = new Process([
			'ln -s ' . getcwd() . '/releases/' . $input->getArgument('release') . ' ' . getcwd() . '/current',
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