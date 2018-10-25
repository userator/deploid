<?php

require __DIR__ . '/../vendor/autoload.php';

$application = new \Deploid\Application('Deploid', '1.0.0');

$application->add(new \Deploid\Command\StructureInit());
$application->add(new \Deploid\Command\StructureValidate());
$application->add(new \Deploid\Command\StructureClean());
$application->add(new \Deploid\Command\StructureRepair());
$application->add(new \Deploid\Command\ReleaseCreate());
$application->add(new \Deploid\Command\ReleaseRemove());
$application->add(new \Deploid\Command\ReleaseExist());
$application->add(new \Deploid\Command\ReleaseCurrent());
$application->add(new \Deploid\Command\ReleaseList());
$application->add(new \Deploid\Command\ReleaseSwitch());
$application->add(new \Deploid\Command\ReleaseRotate());
$application->add(new \Deploid\Command\ReleaseLatest());

$application->run();
