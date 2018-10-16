<?php

namespace Deploid\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * @method \Deploid\Application getApplication() return application object
 */
class ReleaseRemove extends Command {

	protected function configure() {
		$this->setName('release:remove');
		$this->setDescription('Remove release directory');
		$this->setHelp('This command remove a release directory');
		$this->addArgument('release', InputArgument::REQUIRED, 'release directory name');
		$this->addArgument('path', InputArgument::OPTIONAL, 'path to workdir', getcwd());
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