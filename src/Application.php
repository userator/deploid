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
			$payload->setMessage('directory "' . $releasesDir . '" does not exist');
			$payload->setCode(255);
			return $payload;
		}

		$sharedDir = realpath($path) . DIRECTORY_SEPARATOR . 'shared';
		$proccess = new Process('test -d ' . $sharedDir);
		$proccess->run();

		if (!$proccess->isSuccessful()) {
			$payload->setType(Payload::STRUCTURE_VALIDATE_FAIL);
			$payload->setMessage('directory "' . $sharedDir . '" does not exist');
			$payload->setCode(255);
			return $payload;
		}

		$logFile = realpath($path) . DIRECTORY_SEPARATOR . 'deploid.log';
		$proccess = new Process('test -f ' . $logFile);
		$proccess->run();

		if (!$proccess->isSuccessful()) {
			$payload->setType(Payload::STRUCTURE_VALIDATE_FAIL);
			$payload->setMessage('file "' . $logFile . '" does not exist');
			$payload->setCode(255);
			return $payload;
		}

		$payload->setType(Payload::STRUCTURE_VALIDATE_SUCCESS);
		$payload->setMessage('structure is valid');
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
			$payload->setMessage('empty path');
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
	 * @param string $path
	 * @return \Deploid\Payload
	 */
	public function deploidStructureClean($path) {
		$payload = new Payload();

		if (!strlen($path)) {
			$payload->setType(Payload::STRUCTURE_CLEAN_FAIL);
			$payload->setMessage('empty path');
			$payload->setCode(255);
			return $payload;
		}

		$path = $this->absolutePath($path, getcwd());

		$paths = glob(realpath($path) . DIRECTORY_SEPARATOR . '*');

		$needed = [
			'current',
			'releases',
			'shared',
			'deploid.log',
		];

		$paths = array_filter($paths, function ($path) use ($needed) {
			return !in_array(basename($path), $needed);
		});

		foreach ($paths as $path) {
			if (is_dir($path)) rmdir($path);
			if (is_file($path)) unlink($path);
		}

		$payload->setType(Payload::STRUCTURE_CLEAN_SUCCESS);
		$payload->setMessage(array_merge(['cleaned items:'], $paths));
		$payload->setCode(0);
		return $payload;
	}

	/**
	 * @param string $path
	 * @return \Deploid\Payload
	 */
	public function deploidStructureRepair($path) {
		$payload = new Payload();

		if (!strlen($path)) {
			$payload->setType(Payload::STRUCTURE_REPAIR_FAIL);
			$payload->setMessage('empty path');
			$payload->setCode(255);
			return $payload;
		}

		$path = $this->absolutePath($path, getcwd());

		if (!is_dir($path)) {
			$payload->setType(Payload::STRUCTURE_REPAIR_FAIL);
			$payload->setMessage('path does not exist');
			$payload->setCode(255);
			return $payload;
		}

		if (!is_writable($path)) {
			$payload->setType(Payload::STRUCTURE_REPAIR_FAIL);
			$payload->setMessage('path does not writable');
			$payload->setCode(255);
			return $payload;
		}

		/* repairing */

		$messages = [];

		$releasesDir = $path . DIRECTORY_SEPARATOR . 'releases';
		if (!is_dir($releasesDir)) {
			if (mkdir($releasesDir, 0777)) {
				$messages[] = 'directory "' . realpath($releasesDir) . '"';
			} else {
				$payload->setType(Payload::STRUCTURE_REPAIR_FAIL);
				$payload->setMessage('directory "' . $releasesDir . '" does not repair');
				$payload->setCode(255);
				return $payload;
			}
		}

		$sharedDir = $path . DIRECTORY_SEPARATOR . 'shared';
		if (!is_dir($sharedDir)) {
			if (mkdir($sharedDir, 0777)) {
				$messages[] = 'directory "' . realpath($sharedDir) . '"';
			} else {
				$payload->setType(Payload::STRUCTURE_REPAIR_FAIL);
				$payload->setMessage('directory "' . $sharedDir . '" does not repair');
				$payload->setCode(255);
				return $payload;
			}
		}

		$logFile = $path . DIRECTORY_SEPARATOR . 'deploid.log';
		if (!is_file($logFile)) {
			if (touch($logFile)) {
				$messages[] = 'file "' . realpath($logFile) . '"';
			} else {
				$payload->setType(Payload::STRUCTURE_REPAIR_FAIL);
				$payload->setMessage('file"' . $logFile . '" does not repair');
				$payload->setCode(255);
				return $payload;
			}
		}

		$messages = (count($messages) > 0) ? array_merge(['repaired:'], $messages) : 'does not need repairs';

		$payload->setType(Payload::STRUCTURE_REPAIR_SUCCESS);
		$payload->setMessage($messages);
		$payload->setCode(0);
		return $payload;
	}

	/**
	 * @param string $release
	 * @param string $path
	 * @return \Deploid\Payload
	 */
	public function deploidReleaseExist($release, $path) {
		$payload = new Payload();

		if (!strlen($release)) {
			$payload->setType(Payload::RELEASE_EXIST_FAIL);
			$payload->setMessage('empty release name');
			$payload->setCode(255);
			return $payload;
		}

		if (!strlen($path)) {
			$payload->setType(Payload::RELEASE_EXIST_FAIL);
			$payload->setMessage('empty path');
			$payload->setCode(255);
			return $payload;
		}

		$proccess = new Process('test -d ' . realpath($path) . DIRECTORY_SEPARATOR . 'releases' . DIRECTORY_SEPARATOR . $release);
		$proccess->run();

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
		$payload = new Payload();

		if (!strlen($release)) {
			$payload->setType(Payload::RELEASE_CREATE_FAIL);
			$payload->setMessage('empty release name');
			$payload->setCode(255);
			return $payload;
		}

		if (!strlen($path)) {
			$payload->setType(Payload::RELEASE_CREATE_FAIL);
			$payload->setMessage('empty path');
			$payload->setCode(255);
			return $payload;
		}

		$proccess = new Process('mkdir ' . realpath($path) . DIRECTORY_SEPARATOR . 'releases' . DIRECTORY_SEPARATOR . $release);
		$proccess->run();

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
		$payload = new Payload();

		if (!strlen($release)) {
			$payload->setType(Payload::RELEASE_REMOVE_FAIL);
			$payload->setMessage('empty release name');
			$payload->setCode(255);
			return $payload;
		}

		if (!strlen($path)) {
			$payload->setType(Payload::RELEASE_REMOVE_FAIL);
			$payload->setMessage('empty path');
			$payload->setCode(255);
			return $payload;
		}

		$proccess = new Process('rm -r ' . realpath($path) . DIRECTORY_SEPARATOR . 'releases' . DIRECTORY_SEPARATOR . $release);
		$proccess->run();

		if (!$proccess->isSuccessful()) {
			$payload->setType(Payload::RELEASE_REMOVE_FAIL);
			$payload->setMessage($proccess->getErrorOutput());
			$payload->setCode($proccess->getExitCode());
			return $payload;
		}

		$payload->setType(Payload::RELEASE_REMOVE_SUCCESS);
		$payload->setMessage('release "' . ($release == "*" ? 'all' : $release) . '" removed');
		$payload->setCode(0);
		return $payload;
	}

	/**
	 * @param string $path
	 * @return \Deploid\Payload
	 */
	public function deploidReleaseList($path) {
		$payload = new Payload();

		if (!strlen($path)) {
			$payload->setType(Payload::RELEASE_LIST_FAIL);
			$payload->setMessage('path "' . $path . '" invalid');
			$payload->setCode(255);
			return $payload;
		}

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
	 * @param string $path
	 * @return \Deploid\Payload
	 */
	public function deploidReleaseLatest($path) {
		$payload = new Payload();

		if (!strlen($path)) {
			$payload->setType(Payload::RELEASE_LATEST_FAIL);
			$payload->setMessage('path "' . $path . '" invalid');
			$payload->setCode(255);
			return $payload;
		}

		$dirs = glob(realpath($path) . DIRECTORY_SEPARATOR . 'releases' . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);

		if (!$dirs) {
			$payload->setType(Payload::RELEASE_LATEST_FAIL);
			$payload->setMessage('release not found');
			$payload->setCode(255);
			return $payload;
		}

		$dirs = array_map(function ($path) {
			return basename($path);
		}, $dirs);
		
		if (!rsort($dirs)) {
			$payload->setType(Payload::RELEASE_LATEST_FAIL);
			$payload->setMessage('fail sorted');
			$payload->setCode(255);
			return $payload;
		}

		$payload->setType(Payload::RELEASE_LATEST_SUCCESS);
		$payload->setMessage(current($dirs));
		$payload->setCode(0);
		return $payload;
	}

	/**
	 * @param string $path
	 * @return \Deploid\Payload
	 */
	public function deploidReleaseCurrent($path) {
		$payload = new Payload();

		if (!strlen($path)) {
			$payload->setType(Payload::RELEASE_CURRENT_FAIL);
			$payload->setMessage('path "' . $path . '" invalid');
			$payload->setCode(255);
			return $payload;
		}

		$path = $this->absolutePath($path, getcwd());

		$link = $path . DIRECTORY_SEPARATOR . 'current';

		if (!file_exists($link)) {
			$payload->setType(Payload::RELEASE_CURRENT_FAIL);
			$payload->setMessage('current release does not exist');
			$payload->setCode(255);
			return $payload;
		}

		if (!is_link($link)) {
			$payload->setType(Payload::RELEASE_CURRENT_FAIL);
			$payload->setMessage('link to current release does not exist');
			$payload->setCode(255);
			return $payload;
		}

		$linkpath = readlink($link);

		if (!$linkpath) {
			$payload->setType(Payload::RELEASE_CURRENT_FAIL);
			$payload->setMessage('fail read link to current release');
			$payload->setCode(255);
			return $payload;
		}

		$payload->setType(Payload::RELEASE_CURRENT_SUCCESS);
		$payload->setMessage(basename($linkpath));
		$payload->setCode(0);
		return $payload;
	}

	/**
	 * @param string $release
	 * @param string $path
	 * @return \Deploid\Payload
	 */
	public function deploidReleaseSwitch($release, $path) {
		$payload = new Payload();

		if (!strlen($path)) {
			$payload->setType(Payload::RELEASE_SWITCH_FAIL);
			$payload->setMessage('path "' . $path . '" invalid');
			$payload->setCode(255);
			return $payload;
		}

		$releaseDir = realpath($path) . DIRECTORY_SEPARATOR . 'releases' . DIRECTORY_SEPARATOR . $release;
		$currentDir = realpath($path) . DIRECTORY_SEPARATOR . 'current';

		$proccess = new Process('ln -sfn ' . $releaseDir . ' ' . $currentDir);
		$proccess->run();

		if (!$proccess->isSuccessful()) {
			$payload->setType(Payload::RELEASE_SWITCH_FAIL);
			$payload->setMessage($proccess->getErrorOutput());
			$payload->setCode($proccess->getExitCode());
			return $payload;
		}

		$payload->setType(Payload::RELEASE_SWITCH_SUCCESS);
		$payload->setMessage('release "' . $release . '" switched');
		$payload->setCode(0);
		return $payload;
	}

	/**
	 * @param int $quantity
	 * @param string $path
	 * @return \Deploid\Payload
	 */
	public function deploidReleaseRotate($quantity, $path) {
		$payload = new Payload();

		if (!$quantity || $quantity < 1) {
			$payload->setType(Payload::RELEASE_ROTATE_FAIL);
			$payload->setMessage('empty or invalid quantity');
			$payload->setCode(255);
			return $payload;
		}

		if (!strlen($path)) {
			$payload->setType(Payload::RELEASE_ROTATE_FAIL);
			$payload->setMessage('empty path');
			$payload->setCode(255);
			return $payload;
		}

		$path = $this->absolutePath($path, getcwd());

		$releases = glob(realpath($path) . DIRECTORY_SEPARATOR . 'releases' . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);

		if (count($releases) <= $quantity) {
			$payload->setType(Payload::RELEASE_ROTATE_SUCCESS);
			$payload->setMessage('not found releases to rotate');
			$payload->setCode(0);
		}

		foreach (array_reverse($releases) as $idx => $release) {
			if ($idx <= ($quantity - 1)) continue;
			$proccess = new Process('rm -r ' . $release);
			$proccess->run();
		}

		$payload->setType(Payload::RELEASE_ROTATE_SUCCESS);
		$payload->setMessage('release rotated');
		$payload->setCode(0);
		return $payload;
	}

	public function absolutePath($path, $cwd) {
		if ($path[0] == '/') return $path;
		return $cwd . DIRECTORY_SEPARATOR . $path;
	}

}