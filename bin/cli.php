#!/usr/bin/php
<?php
require __DIR__ . '/../vendor/autoload.php';

$application = new Symfony\Component\Console\Application('Deployd', '1.0.0');

$application->add(new \Deploid\Command\Init());
$application->add(new \Deploid\Command\Create());
$application->add(new \Deploid\Command\Remove());
$application->add(new \Deploid\Command\Current());

$application->run();
