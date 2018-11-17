<?php

namespace Deploid;

use Symfony\Component\Console\Application as ConsoleApplication;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

class Application extends ConsoleApplication implements LoggerAwareInterface {

	/** @var string */
	private $releaseNameFormat = 'Y-m-d_H-i-s';

	/** @var string */
	private $chmod = 0777;

	/** @var array */
	private $structure = [
		'dirs' => [
			'releases',
			'releases/first',
			'shared',
		],
		'files' => [
			'deploid.log',
		],
		'links' => [
			'current:releases/first',
		],
	];

	/** @var array */
	private $mapping = [
		'releasesDir' => 'releases',
		'logFile' => 'deploid.log',
		'currentLink' => 'current',
	];

	/** @var LoggerInterface */
	private $logger;

	/* mutators */

	public function getReleaseNameFormat() {
		return $this->releaseNameFormat;
	}

	public function setReleaseNameFormat($releaseNameFormat) {
		$this->releaseNameFormat = $releaseNameFormat;
	}

	public function getChmod() {
		return $this->chmod;
	}

	public function setChmod($chmod) {
		$this->chmod = $chmod;
	}

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

	public function getStructure() {
		return $this->structure;
	}

	public function setStructure(array $structure) {
		$this->structure = $structure;
	}

	public function getMapping() {
		return $this->mapping;
	}

	public function setMapping($mapping) {
		$this->mapping = $mapping;
	}

	/* tools */

	public function parseConfig($config) {
		if (isset($config['releaseNameFormat'])) $this->releaseNameFormat = $config['releaseNameFormat'];
		if (isset($config['chmod'])) $this->chmod = $config['chmod'];
		if (isset($config['structure'])) $this->structure = $config['structure'];
		if (isset($config['mapping'])) $this->mapping = $config['mapping'];
	}

	public function makeStructure($path, array $structure) {
		if (empty($path)) throw new \InvalidArgumentException('empty path');

		if (empty($structure)) true;

		foreach ($structure as $section => $items) {
			if (empty($items)) continue;
			foreach ($items as $item) {
				if (empty($item)) continue;
				if ($section == 'dirs') {
					$dir = $path . DIRECTORY_SEPARATOR . $item;
					mkdir($dir, $this->chmod, true);
				} else if ($section == 'files') {
					$file = $path . DIRECTORY_SEPARATOR . $item;
					if (!is_dir(dirname($file))) mkdir(dirname($file), $this->chmod, true);
					touch($path . DIRECTORY_SEPARATOR . $item);
				} else if ($section == 'links') {
					$link = $path . DIRECTORY_SEPARATOR . (explode(':', $item)[0]);
					$target = $path . DIRECTORY_SEPARATOR . (explode(':', $item)[1]);
					if (!is_dir(dirname($link))) mkdir(dirname($link), $this->chmod, true);
					symlink($target, $link);
				}
			}
		}

		return true;
	}

	public function scanStructure($path) {
		if (empty($path)) throw new \InvalidArgumentException('empty path');

		$directory = new \RecursiveDirectoryIterator(realpath($path), \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS);
		$iterator = new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::SELF_FIRST);

		$items = [];
		foreach ($iterator as $item) {
			$items[] = $item;
		}

		if (empty($items)) return [];

		$structure = [];
		foreach ($items as $item) {
			if (is_link($item)) {
				$structure['links'][] = str_ireplace(realpath($path) . DIRECTORY_SEPARATOR, '', $item) . ':' . str_ireplace(realpath($path) . DIRECTORY_SEPARATOR, '', readlink($item));
			} else if (is_file($item)) {
				$structure['files'][] = str_ireplace(realpath($path) . DIRECTORY_SEPARATOR, '', $item);
			} else if (is_dir($item)) {
				$structure['dirs'][] = str_ireplace(realpath($path) . DIRECTORY_SEPARATOR, '', $item);
			}
		}

		return $structure;
	}

	public function sortStructure(array $structure) {
		ksort($structure);

		$structure = array_map(function ($item) {
			sort($item);
			return $item;
		}, $structure);

		return $structure;
	}

	public function diffStructure(array $structureThin, array $structureFat) {
		if (empty($structureThin)) return [];
		if (empty($structureFat)) return [];

		$structureDiff = [];

		foreach ($structureFat as $section => $items) {
			if (empty($structureThin[$section])) continue;
			$diff = array_diff($items, $structureThin[$section]);
			if (empty($diff)) continue;
			$structureDiff[$section] = $diff;
		}

		return $structureDiff;
	}

	public function toRealpaths($path, array $structure) {
		if (empty($path)) throw new \InvalidArgumentException('empty path');

		if (empty($structure)) return [];

		$realpaths = [];

		foreach ($structure as $section => $items) {
			if (empty($items)) continue;
			foreach ($items as $item) {
				if (empty($item)) continue;
				if ($section == 'links') {
					$realpaths[] = realpath($path) . DIRECTORY_SEPARATOR . explode(':', $item)[0];
				} else if ($section == 'files') {
					$realpaths[] = realpath($path) . DIRECTORY_SEPARATOR . $item;
				} else if ($section == 'dirs') {
					$realpaths[] = realpath($path) . DIRECTORY_SEPARATOR . $item;
				}
			}
		}

		rsort($realpaths);

		return $realpaths;
	}

	public function absolutePath($path, $cwd) {
		if (empty($path)) throw new \InvalidArgumentException('empty path');
		if (empty($cwd)) throw new \InvalidArgumentException('empty cwd');

		if ($path[0] == '/') return $path;

		return $cwd . DIRECTORY_SEPARATOR . $path;
	}

	/* business logic */

	/**
	 * @param string $path
	 * @return \Deploid\Payload
	 */
	public function deploidStructureValidate($path) {
		$payload = new Payload();

		if (!strlen($path)) {
			$payload->setType(Payload::STRUCTURE_VALIDATE_FAIL);
			$payload->setMessage('empty path');
			$payload->setCode(255);
			return $payload;
		}

		$path = $this->absolutePath($path, getcwd());
		$messages = [];

		foreach ($this->structure as $section => $items) {
			if (empty($items)) continue;
			foreach ($items as $item) {
				if (empty($item)) continue;
				if ($section == 'dirs') {
					$dir = $path . DIRECTORY_SEPARATOR . $item;
					if (!is_dir($dir)) $messages[] = 'directory "' . $dir . '" not found';
				} else if ($section == 'files') {
					$file = $path . DIRECTORY_SEPARATOR . $item;
					if (!is_file($file)) $messages[] = 'file "' . $file . '" not found';
				} else if ($section == 'links') {
					$link = $path . DIRECTORY_SEPARATOR . (explode(':', $item)[0]);
					if (!is_link($link)) $messages[] = 'link "' . realpath($link) . '" not found';
				}
			}
		}

		if (count($messages)) {
			$payload->setType(Payload::STRUCTURE_VALIDATE_FAIL);
			$payload->setMessage($messages);
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

		$messagesSuccess = [];
		$messagesFail = [];

		if (!is_dir($path)) {
			if (mkdir($path, $this->chmod, true)) {
				$messagesSuccess[] = 'directory "' . realpath($path) . '" created';
			} else {
				$messagesFail[] = 'directory "' . $path . '" does not created';
			}
		}


		foreach ($this->structure as $section => $items) {
			if (empty($items)) continue;
			foreach ($items as $item) {
				if (empty($item)) continue;
				if ($section == 'dirs') {
					$dir = $path . DIRECTORY_SEPARATOR . $item;
					if (mkdir($dir, $this->chmod, true)) {
						$messagesSuccess[] = 'directory "' . $dir . '" created';
					} else {
						$messagesFail[] = 'directory "' . $dir . '" does not created';
					}
				} else if ($section == 'files') {
					$file = $path . DIRECTORY_SEPARATOR . $item;
					if (touch($file)) {
						$messagesSuccess[] = 'file "' . $file . '" created';
					} else {
						$messagesFail[] = 'file"' . $file . '" does not created';
					}
				} else if ($section == 'links') {
					$target = $path . DIRECTORY_SEPARATOR . (explode(':', $item)[1]);
					$link = $path . DIRECTORY_SEPARATOR . (explode(':', $item)[0]);
					if (symlink($target, $link)) {
						$messagesSuccess[] = 'link "' . $link . '" created';
					} else {
						$messagesFail[] = 'link ' . $link . '" does not created';
					}
				}
			}
		}

		if (count($messagesFail)) {
			$payload->setType(Payload::STRUCTURE_INIT_FAIL);
			$payload->setMessage($messagesFail);
			$payload->setCode(255);
		}

		$payload->setType(Payload::STRUCTURE_INIT_SUCCESS);
		$payload->setMessage($messagesSuccess);
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

		$realpathsClean = $this->toRealpaths($path, $this->structure);
		$realpathsDirty = $this->toRealpaths($path, $this->scanStructure($path));
		$realpathsDiff = array_diff($realpathsDirty, $realpathsClean);

		foreach ($realpathsDiff as $item) {
			if (empty($item)) continue;
			if (is_link($item)) unlink($item);
			if (is_file($item)) unlink($item);
			if (is_dir($item)) rmdir($item);
		}

		$payload->setType(Payload::STRUCTURE_CLEAN_SUCCESS);
		$payload->setMessage(array_merge(['cleaned items:'], $realpathsDiff));
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

		$releaseDir = $path . DIRECTORY_SEPARATOR . $this->mapping['releasesDir'] . DIRECTORY_SEPARATOR . $release;
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

		$releaseDir = realpath($path) . DIRECTORY_SEPARATOR . $this->mapping['releasesDir'] . DIRECTORY_SEPARATOR . $release;

		if (!mkdir($releaseDir, $this->chmod, true)) {
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

		$proccess = new Process('rm -r ' . realpath($path) . DIRECTORY_SEPARATOR . $this->mapping['releasesDir'] . DIRECTORY_SEPARATOR . $release);
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

		$dirs = glob(realpath($path) . DIRECTORY_SEPARATOR . $this->mapping['releasesDir'] . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);

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

		$dirs = glob(realpath($path) . DIRECTORY_SEPARATOR . $this->mapping['releasesDir'] . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);

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

		$link = realpath($path) . DIRECTORY_SEPARATOR . $this->mapping['currentLink'];

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

		$releaseDir = realpath($path) . DIRECTORY_SEPARATOR . $this->mapping['releasesDir'] . DIRECTORY_SEPARATOR . $release;
		$currentLink = realpath($path) . DIRECTORY_SEPARATOR . $this->mapping['currentLink'];

		$proccess = new Process('ln -sfn ' . $releaseDir . ' ' . $currentLink);
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

		$releases = glob(realpath($path) . DIRECTORY_SEPARATOR . $this->mapping['releasesDir'] . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);

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

}