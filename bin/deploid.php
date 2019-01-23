<?php

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Console\ConsoleEvents;
use Deploid\Application;

$dispatcher = new EventDispatcher();
$dispatcher->addListener(ConsoleEvents::COMMAND, [Application::class, 'initListener']);

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
