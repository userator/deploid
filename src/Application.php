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
	 * @return void
	 */
	public function setLogger(LoggerInterface $logger) {
		$this->logger = $logger;
	}

	/* tools */

	/**
	 * @param string $path
	 * @return \Deploid\Payload
	 */
	public function deploidStructureValidate($path) {
		$payload = new Payload();

		$path = $this->absolutePath($path, getcwd());

		$releasesDir = realpath($path) . DIRECTORY_SEPARATOR . 'releases';
		if (!is_dir($releasesDir)) {
			$payload->setType(Payload::STRUCTURE_VALIDATE_FAIL);
			$payload->setMessage('directory "' . $releasesDir . '" does not exist');
			$payload->setCode(255);
			return $payload;
		}

		$sharedDir = realpath($path) . DIRECTORY_SEPARATOR . 'shared';
		if (!is_dir($sharedDir)) {
			$payload->setType(Payload::STRUCTURE_VALIDATE_FAIL);
			$payload->setMessage('directory "' . $sharedDir . '" does not exist');
			$payload->setCode(255);
			return $payload;
		}

		$logFile = realpath($path) . DIRECTORY_SEPARATOR . 'deploid.log';
		if (!is_file($logFile)) {
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
		$messages = [];

		if (!is_dir($path)) {
			if (mkdir($path, 0777, true)) {
				$messages[] = 'directory "' . realpath($path) . '" created';
			} else {
				$payload->setType(Payload::STRUCTURE_INIT_FAIL);
				$payload->setMessage('directory "' . $path . '" does not created');
				$payload->setCode(255);
				return $payload;
			}
		}

		$releasesDir = $path . DIRECTORY_SEPARATOR . 'releases';
		if (!is_dir($releasesDir)) {
			if (mkdir($releasesDir, 0777, true)) {
				$messages[] = 'directory "' . realpath($releasesDir) . '" created';
			} else {
				$payload->setType(Payload::STRUCTURE_INIT_FAIL);
				$payload->setMessage('directory "' . $releasesDir . '" does not created');
				$payload->setCode(255);
				return $payload;
			}
		}

		$sharedDir = $path . DIRECTORY_SEPARATOR . 'shared';
		if (!is_dir($sharedDir)) {
			if (mkdir($sharedDir, 0777, true)) {
				$messages[] = 'directory "' . realpath($sharedDir) . '" created';
			} else {
				$payload->setType(Payload::STRUCTURE_INIT_FAIL);
				$payload->setMessage('directory "' . $sharedDir . '" does not created');
				$payload->setCode(255);
				return $payload;
			}
		}

		$logFile = $path . DIRECTORY_SEPARATOR . 'deploid.log';
		if (!is_file($logFile)) {
			if (touch($logFile)) {
				$messages[] = 'file "' . realpath($logFile) . '" created';
			} else {
				$payload->setType(Payload::STRUCTURE_INIT_FAIL);
				$payload->setMessage('file"' . $logFile . '" does not created');
				$payload->setCode(255);
				return $payload;
			}
		}

		$payload->setType(Payload::STRUCTURE_INIT_SUCCESS);
		$payload->setMessage($messages);
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

		foreach ($paths as $pathname) {
			if (is_dir($pathname)) {
				rmdir($pathname);
			} else if (is_file($pathname)) {
				unlink($pathname);
			}
		}

		$payload->setType(Payload::STRUCTURE_CLEAN_SUCCESS);
		$payload->setMessage(array_merge(['cleaned items:'], $paths));
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

		$path = $this->absolutePath($path, getcwd());

		$releaseDir = $path . DIRECTORY_SEPARATOR . 'releases' . DIRECTORY_SEPARATOR . $release;
		if (!is_dir($releaseDir)) {
			$payload->setType(Payload::RELEASE_EXIST_FAIL);
			$payload->setMessage('release "' . $release . '" does not exist');
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

		$path = $this->absolutePath($path, getcwd());

		$releaseDir = realpath($path) . DIRECTORY_SEPARATOR . 'releases' . DIRECTORY_SEPARATOR . $release;

		if (!mkdir($releaseDir, 0777, true)) {
			$payload->setType(Payload::RELEASE_CREATE_FAIL);
			$payload->setMessage('release "' . $release . '" does not created');
			$payload->setCode(255);
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

		$path = $this->absolutePath($path, getcwd());

		$proccess = new Process('rm -r ' . realpath($path) . DIRECTORY_SEPARATOR . 'releases' . DIRECTORY_SEPARATOR . $release);
		$proccess->run();

		if (!$proccess->isSuccessful()) {
			$payload->setType(Payload::RELEASE_REMOVE_FAIL);
			$payload->setMessage($proccess->getErrorOutput());
			$payload->setCode($proccess->getExitCode());
			return $payload;
		}

		$payload->setType(Payload::RELEASE_REMOVE_SUCCESS);
		$payload->setMessage('release "' . ($release == '*' ? 'all' : $release) . '" removed');
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

		$path = $this->absolutePath($path, getcwd());

		$dirs = glob(realpath($path) . DIRECTORY_SEPARATOR . 'releases' . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);

		if (empty($dirs)) {
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
			$payload->setMessage('empty path');
			$payload->setCode(255);
			return $payload;
		}

		$path = $this->absolutePath($path, getcwd());

		$dirs = glob(realpath($path) . DIRECTORY_SEPARATOR . 'releases' . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);

		if (empty($dirs)) {
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
			$payload->setMessage('empty path');
			$payload->setCode(255);
			return $payload;
		}

		$path = $this->absolutePath($path, getcwd());

		$link = realpath($path) . DIRECTORY_SEPARATOR . 'current';

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
	public function deploidReleaseSetup($release, $path) {
		$payload = new Payload();

		if (!strlen($path)) {
			$payload->setType(Payload::RELEASE_SETUP_FAIL);
			$payload->setMessage('path "' . $path . '" invalid');
			$payload->setCode(255);
			return $payload;
		}

		$path = $this->absolutePath($path, getcwd());

		$releaseDir = realpath($path) . DIRECTORY_SEPARATOR . 'releases' . DIRECTORY_SEPARATOR . $release;
		$currentDir = realpath($path) . DIRECTORY_SEPARATOR . 'current';

		$proccess = new Process('ln -sfn ' . $releaseDir . ' ' . $currentDir);
		$proccess->run();

		if (!$proccess->isSuccessful()) {
			$payload->setType(Payload::RELEASE_SETUP_FAIL);
			$payload->setMessage($proccess->getErrorOutput());
			$payload->setCode($proccess->getExitCode());
			return $payload;
		}

		$payload->setType(Payload::RELEASE_SETUP_SUCCESS);
		$payload->setMessage('release "' . $release . '" setup');
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
		$payload->setMessage('releases are rotated');
		$payload->setCode(0);
		return $payload;
	}

	public function absolutePath($path, $cwd) {
		if ($path[0] == '/') return $path;
		return $cwd . DIRECTORY_SEPARATOR . $path;
	}

}