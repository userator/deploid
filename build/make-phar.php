<?php

ini_set("phar.readonly", 0);

$pharFile = __DIR__ . '/../build/deploid.phar';

if (!is_dir(__DIR__ . '/../vendor')) {
	echo 'folder "vendor" does not exist' . PHP_EOL;
	exit(255);
};

// clean up
if (file_exists($pharFile)) unlink($pharFile);

try {
	$phar = new Phar($pharFile);
	$phar->buildFromDirectory(realpath(__DIR__ . '/..'), '/^' . preg_quote(realpath(__DIR__ . '/../bin'), '/') . '.+\.php$/');
	$phar->buildFromDirectory(realpath(__DIR__ . '/..'), '/^' . preg_quote(realpath(__DIR__ . '/../src'), '/') . '.+\.php$/');
	$phar->buildFromDirectory(realpath(__DIR__ . '/..'), '/^' . preg_quote(realpath(__DIR__ . '/../vendor'), '/') . '.+\.php$/');
	$phar->setStub("#!/usr/bin/env php \n" . $phar->createDefaultStub('bin/deploid.php', 'bin/deploid.php'));
	echo realpath($pharFile) . PHP_EOL;
} catch (\Exception $e) {
	echo $e . PHP_EOL;
	exit(255);
}
