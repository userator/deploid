<?php

namespace Deploid;

use Symfony\Component\Console\Application as ConsoleApplication;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

class Application extends ConsoleApplication implements LoggerAwareInterface {

	/** @var LoggerInterface */
	private $logger;

	/* mutators */

	/**
	 * @return LoggerInterface
	 */
	public function getLogger() {
		return $this->logger;
	}

	/**
	 * @param LoggerInterface $logger
	 * @return $this
	 */
	public function setLogger(LoggerInterface $logger) {
		$this->logger = $logger;
		return $this;
	}

	/* tools */

	/**
	 * @param string $path
	 * @return \Deploid\Payload
	 */
	public function deploidStructureValidate($path) {
		$payload = new Payload();

		$releasesDir = realpath($path) . DIRECTORY_SEPARATOR . 'releases';
		$proccess = new Process('test -d ' . $releasesDir);
		$proccess->run();

		if (!$proccess->isSuccessful()) {
			$payload->setType(Payload::STRUCTURE_VALIDATE_FAIL);
			$payload->setMessage('releases directory "' . $releasesDir . '" not exist');
			$payload->setCode(255);
			return $payload;
		}

		$sharedDir = realpath($path) . DIRECTORY_SEPARATOR . 'shared';
		$proccess = new Process('test -d ' . $sharedDir);
		$proccess->run();

		if (!$proccess->isSuccessful()) {
			$payload->setType(Payload::STRUCTURE_VALIDATE_FAIL);
			$payload->setMessage('shared directory "' . $sharedDir . '" not exist');
			$payload->setCode(255);
			return $payload;
		}

		$logFile = realpath($path) . DIRECTORY_SEPARATOR . 'deploid.log';
		$proccess = new Process('test -f ' . $logFile);
		$proccess->run();

		if (!$proccess->isSuccessful()) {
			$payload->setType(Payload::STRUCTURE_VALIDATE_FAIL);
			$payload->setMessage('log file "' . $logFile . '" not exist');
			$payload->setCode(255);
			return $payload;
		}

		$payload->setType(Payload::STRUCTURE_VALIDATE_SUCCESS);
		$payload->setMessage('valid structure in path "' . realpath($path) . '"');
		$payload->setCode(0);
		return $payload;
	}

	/**
	 * @param string $path
	 * @return \Deploid\Payload
	 */
	public function deploidStructureInit($path) {
		$payload = new Payload();

		if (!strlen($path)) {
			$payload->setType(Payload::STRUCTURE_INIT_FAIL);
			$payload->setMessage('path "' . $path . '" invalid');
			$payload->setCode(255);
			return $payload;
		}

		$path = $this->absolutePath($path, getcwd());

		if (is_file($path)) {
			$payload->setType(Payload::STRUCTURE_INIT_FAIL);
			$payload->setMessage('path "' . $path . '" not dir');
			$payload->setCode(255);
			return $payload;
		}

		if (!is_writable(dirname($path))) {
			$payload->setType(Payload::STRUCTURE_INIT_FAIL);
			$payload->setMessage('path "' . dirname($path) . '" not writable');
			$payload->setCode(255);
			return $payload;
		}

		if (!file_exists($path) && !mkdir($path, 0777)) {
			$payload->setType(Payload::STRUCTURE_INIT_FAIL);
			$payload->setMessage('path "' . $path . '" not create');
			$payload->setCode(255);
			return $payload;
		}

		if (!is_writable($path)) {
			$payload->setType(Payload::STRUCTURE_INIT_FAIL);
			$payload->setMessage('path "' . $path . '" not writable');
			$payload->setCode(255);
			return $payload;
		}

		$proccess = new Process('mkdir ' . realpath($path) . DIRECTORY_SEPARATOR . 'releases');
		$proccess->run();

		if (!$proccess->isSuccessful()) {
			$payload->setType(Payload::STRUCTURE_INIT_FAIL);
			$payload->setMessage($proccess->getErrorOutput());
			$payload->setCode($proccess->getExitCode());
			return $payload;
		}

		$proccess = new Process('mkdir ' . realpath($path) . DIRECTORY_SEPARATOR . 'shared');
		$proccess->run();

		if (!$proccess->isSuccessful()) {
			$payload->setType(Payload::STRUCTURE_INIT_FAIL);
			$payload->setMessage($proccess->getErrorOutput());
			$payload->setCode($proccess->getExitCode());
			return $payload;
		}

		$proccess = new Process('touch ' . realpath($path) . DIRECTORY_SEPARATOR . 'deploid.log');
		$proccess->run();

		if (!$proccess->isSuccessful()) {
			$payload->setType(Payload::STRUCTURE_INIT_FAIL);
			$payload->setMessage($proccess->getErrorOutput());
			$payload->setCode($proccess->getExitCode());
			return $payload;
		}

		$payload->setType(Payload::STRUCTURE_INIT_SUCCESS);
		$payload->setMessage('structure initialized by path "' . $path . '"');
		$payload->setCode(0);
		return $payload;
	}

	/**
	 * @param string $release
	 * @param string $path
	 * @return \Deploid\Payload
	 */
	public function deploidReleaseExist($release, $path) {
		$proccess = new Process('test -d ' . realpath($path) . DIRECTORY_SEPARATOR . 'releases' . DIRECTORY_SEPARATOR . $release);
		$proccess->run();

		$payload = new Payload();

		if (!$proccess->isSuccessful()) {
			$payload->setType(Payload::RELEASE_EXIST_FAIL);
			$payload->setMessage('release "' . $release . '" in path "' . $path . '" not exist');
			$payload->setCode(255);
			return $payload;
		}

		$payload->setType(Payload::RELEASE_EXIST_SUCCESS);
		$payload->setMessage('release "' . $release . '" exist');
		$payload->setCode(0);
		return $payload;
	}

	/**
	 * @param string $release
	 * @param string $path
	 * @return \Deploid\Payload
	 */
	public function deploidReleaseCreate($release, $path) {
		$proccess = new Process('mkdir ' . realpath($path) . DIRECTORY_SEPARATOR . 'releases' . DIRECTORY_SEPARATOR . $release);
		$proccess->run();

		$payload = new Payload();

		if (!$proccess->isSuccessful()) {
			$payload->setType(Payload::RELEASE_CREATE_FAIL);
			$payload->setMessage($proccess->getErrorOutput());
			$payload->setCode($proccess->getExitCode());
			return $payload;
		}

		$payload->setType(Payload::RELEASE_CREATE_SUCCESS);
		$payload->setMessage('release "' . $release . '" created');
		$payload->setCode(0);
		return $payload;
	}

	/**
	 * @param string $release
	 * @param string $path
	 * @return \Deploid\Payload
	 */
	public function deploidReleaseRemove($release, $path) {
		$proccess = new Process('rm -r ' . realpath($path) . DIRECTORY_SEPARATOR . 'releases' . DIRECTORY_SEPARATOR . $release);
		$proccess->run();

		$payload = new Payload();

		if (!$proccess->isSuccessful()) {
			$payload->setType(Payload::RELEASE_REMOVE_FAIL);
			$payload->setMessage($proccess->getErrorOutput());
			$payload->setCode($proccess->getExitCode());
			return $payload;
		}

		$payload->setType(Payload::RELEASE_REMOVE_SUCCESS);
		$payload->setMessage('release "' . $release . '" removed');
		$payload->setCode(0);
		return $payload;
	}

	/**
	 * @param string $path
	 * @return \Deploid\Payload
	 */
	public function deploidReleaseList($path) {
		$payload = new Payload();

		$dirs = glob(realpath($path) . DIRECTORY_SEPARATOR . 'releases' . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);

		if (!$dirs) {
			$payload->setType(Payload::RELEASE_LIST_FAIL);
			$payload->setMessage('release not found');
			$payload->setCode(0);
			return $payload;
		}

		$dirs = array_map(function ($path) {
			return basename($path);
		}, $dirs);

		$payload->setType(Payload::RELEASE_LIST_SUCCESS);
		$payload->setMessage($dirs);
		$payload->setCode(0);
		return $payload;
	}

	/**
	 * @param string $release
	 * @param string $path
	 * @return \Deploid\Payload
	 */
	public function deploidReleaseCurrent($release, $path) {
		$releaseDir = realpath($path) . DIRECTORY_SEPARATOR . 'releases' . DIRECTORY_SEPARATOR . $release;
		$currentDir = realpath($path) . DIRECTORY_SEPARATOR . 'current';

		$proccess = new Process('ln -s ' . $releaseDir . ' ' . $currentDir);
		$proccess->run();

		$payload = new Payload();

		if (!$proccess->isSuccessful()) {
			$payload->setType(Payload::RELEASE_CURRENT_FAIL);
			$payload->setMessage($proccess->getErrorOutput());
			$payload->setCode($proccess->getExitCode());
			return $payload;
		}

		$payload->setType(Payload::RELEASE_CURRENT_SUCCESS);
		$payload->setMessage('release "' . $release . '" current setup');
		$payload->setCode(0);
		return $payload;
	}

	public function absolutePath($path, $cwd) {
		if ($path[0] == '/') return $path;
		return $cwd . DIRECTORY_SEPARATOR . $path;
	}

}