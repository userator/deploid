<?php

ini_set("phar.readonly", 0);

$pharFile = 'bin/deploid.phar';
// clean up
if (file_exists($pharFile)) unlink($pharFile);
if (file_exists($pharFile . '.gz')) unlink($pharFile . '.gz');
// create phar
$p = new Phar($pharFile);
// creating our library using whole directory
$p->buildFromDirectory(__DIR__ . '/../', 'build|LICENSE|composer.json|composer.lock|.gitignore');
// pointing main file which requires all classes
$p->setDefaultStub('bin/deploid');
// plus - compressing it into gzip
$p->compress(Phar::GZ);

echo "success" . PHP_EOL;
