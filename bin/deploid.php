<?php

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Deploid\Application;

$dispatcher = new EventDispatcher();
$dispatcher->addListener(ConsoleEvents::COMMAND, function (ConsoleCommandEvent $event) {
//	if ('structure:init' == $event->getCommand()->getName()) return null;
	if (!$event->getInput()->hasArgument('path')) return null;

	/** @var \Deploid\Application */
	$application = $event->getCommand()->getApplication();

	$parentpath = $application->absolutePath($event->getInput()->getArgument('path'), getcwd());
	$relativepath = $application->getConfigInternal()['mapping']['config-file'];
	$path = $parentpath . DIRECTORY_SEPARATOR . $relativepath;

	$external = (is_file($path)) ? $application->readConfigFile($path) : $application->getConfigDefault();
	$internal = $application->getConfigInternal();

	$config = $application->calcConfig($internal, $external);

	$application->init($config);
});

$application = new Application('Deploid', '1.0.0');
$application->setDispatcher($dispatcher);

$application->add(new \Deploid\Command\StructureInit());
$application->add(new \Deploid\Command\StructureValidate());
$application->add(new \Deploid\Command\StructureClean());
$application->add(new \Deploid\Command\ReleaseCreate());
$application->add(new \Deploid\Command\ReleaseRemove());
$application->add(new \Deploid\Command\ReleaseExist());
$application->add(new \Deploid\Command\ReleaseCurrent());
$application->add(new \Deploid\Command\ReleaseList());
$application->add(new \Deploid\Command\ReleaseSetup());
$application->add(new \Deploid\Command\ReleaseRotate());
$application->add(new \Deploid\Command\ReleaseLatest());

$application->run();
