<?php

namespace Deploid;

use Symfony\Component\Console\Application as ConsoleApplication;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

class Application extends ConsoleApplication implements LoggerAwareInterface {

	/** @var string */
	private $name = 'deploid-app';

	/** @var string */
	private $releaseNameFormat = 'Y-m-d_H-i-s';

	/** @var int */
	private $chmod = 0777;

	/** @var array */
	private $structure = [];

	/** @var array */
	private $mapping = [];

	/** @var array */
	private $configInternal = [
		'structure' => [
			'dirs' => [],
			'files' => [
				'deploid.json',
			],
			'links' => [],
		],
		'mapping' => [
			'config-file' => 'deploid.json',
		],
	];

	/** @var array */
	private $configExternal = [];

	/** @var array */
	private $configDefault = [
		'name' => 'deploid-app',
		'release-name-format' => 'Y-m-d_H-i-s',
		'chmod' => '0777',
		'structure' => [
			'dirs' => [
				'releases',
				'releases/2018-10-16_07-36-05',
				'shared',
			],
			'files' => [
				'deploid.log',
			],
			'links' => [
				'current:releases/2018-10-16_07-36-05',
			],
		],
		'mapping' => [
			'releases-dir' => 'releases',
			'log-file' => 'deploid.log',
			'current-link' => 'current',
		],
	];

	/** @var array */
	private $config = [];

	/** @var LoggerInterface */
	private $logger;

	/* mutators */

	public function getName() {
		return $this->name;
	}

	public function getReleaseNameFormat() {
		return $this->releaseNameFormat;
	}

	public function getChmod() {
		return $this->chmod;
	}

	public function getStructure() {
		return $this->structure;
	}

	public function getMapping() {
		return $this->mapping;
	}

	public function getConfigInternal() {
		return $this->configInternal;
	}

	public function getConfigExternal() {
		return $this->configExternal;
	}

	public function getConfigDefault() {
		return $this->configDefault;
	}

	public function getConfig() {
		return $this->config;
	}

	public function getConfigMaker() {
		return $this->configMaker;
	}

	public function getLogger() {
		return $this->logger;
	}

	public function setName($name) {
		$this->name = $name;
	}

	public function setReleaseNameFormat($releaseNameFormat) {
		$this->releaseNameFormat = $releaseNameFormat;
	}

	public function setChmod($chmod) {
		$this->chmod = $chmod;
	}

	public function setStructure(array $structure) {
		$this->structure = $structure;
	}

	public function setMapping(array $mapping) {
		$this->mapping = $mapping;
	}

	public function setConfigInternal(array $configInternal) {
		$this->configInternal = $configInternal;
	}

	public function setConfigExternal(array $configExternal) {
		$this->configExternal = $configExternal;
	}

	public function setConfigDefault(array $configDefault) {
		$this->configDefault = $configDefault;
	}

	public function setConfig(array $config) {
		$this->config = $config;
	}

	public function setConfigMaker(ConfigMaker $configMaker) {
		$this->configMaker = $configMaker;
	}

	public function setLogger(LoggerInterface $logger) {
		$this->logger = $logger;
	}

	/* tools */

	static public function readConfigFile($file) {
		if (empty($file)) throw new \InvalidArgumentException('empty "file" agrument');
		if (!is_file($file)) throw new \InvalidArgumentException('file "' . $file . '" not found');

		$json = file_get_contents($file);
		if (false === $json) throw new \RuntimeException('unble to read file "' . $file . '"');

		$config = json_decode($json, true);
		if (null === $config) throw new \RuntimeException('unble to decode json');

		return $config;
	}

	static public function writeConfigFile($file, array $config) {
		if (empty($file)) throw new \InvalidArgumentException('empty "file" agrument');
		if (!is_file($file)) throw new \InvalidArgumentException('file "' . $file . '" not found');

		$json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if (false === $json) throw new \RuntimeException('unable to encode json');

		$write = file_put_contents($file, $json);
		if (false === $write) throw new \RuntimeException('unble to write file "' . $file . '"');

		return true;
	}

	public function calcConfig(array $external, array $internal) {
		return array_merge_recursive($external, $internal);
	}

	public function init(array $config) {
		$this->name = $config['name'];
		$this->releaseNameFormat = $config['release-name-format'];
		$this->chmod = intval($config['chmod'], 8);
		$this->structure = $config['structure'];
		$this->mapping = $config['mapping'];
		$this->config = $config;
		return $this;
	}

	public function calcConfigExternal($config, $configInternal) {
		$dirs = array_diff($config['structure']['dirs'], $configInternal['structure']['dirs']);
		if (count($dirs)) $config['structure']['dirs'] = array_values($dirs);

		$files = array_diff($config['structure']['files'], $configInternal['structure']['files']);
		if (count($files)) $config['structure']['files'] = array_values($files);

		$links = array_diff($config['structure']['links'], $configInternal['structure']['links']);
		if (count($links)) $config['structure']['links'] = array_values($links);

		$maping = array_diff_assoc($config['mapping'], $configInternal['mapping']);
		if (count($maping)) $config['mapping'] = $maping;

		return $config;
	}

	public function isEmptyConfigFile($file) {
		if (empty($file)) throw new \InvalidArgumentException('empty "file" agrument');
		if (!is_file($file)) throw new \InvalidArgumentException('file "' . $file . '" not found');

		$json = file_get_contents($file);
		if (false === $json) throw new \RuntimeException('unble to read file "' . $file . '"');

		return empty($json);
	}

	public function prepareConfig(array $config) {
		$config['structure'] = $this->sortStructure($config['structure']);
		$config['structure'] = $this->uniqStructure($config['structure']);
		return $config;
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

	public function uniqStructure(array $structure) {
		$structure = array_map(function ($item) {
			return array_unique($item);
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

	public function flatPaths($path, array $structure) {
		if (empty($path)) throw new \InvalidArgumentException('empty path');

		if (empty($structure)) return [];

		$flatpaths = [];

		foreach ($structure as $section => $items) {
			if (empty($items)) continue;
			foreach ($items as $item) {
				if (empty($item)) continue;
				if ($section == 'links') {
					$flatpaths[] = realpath($path) . DIRECTORY_SEPARATOR . explode(':', $item)[0];
				} else if ($section == 'files') {
					$flatpaths[] = realpath($path) . DIRECTORY_SEPARATOR . $item;
				} else if ($section == 'dirs') {
					$flatpaths[] = realpath($path) . DIRECTORY_SEPARATOR . $item;
				}
			}
		}

		rsort($flatpaths);

		return $flatpaths;
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
					if (is_dir($dir)) {
						$messagesSuccess[] = 'directory "' . $dir . '" already exist';
					} else if (mkdir($dir, $this->chmod, true)) {
						$messagesSuccess[] = 'directory "' . $dir . '" created';
					} else {
						$messagesFail[] = 'directory "' . $dir . '" does not created';
					}
				} else if ($section == 'files') {
					$file = $path . DIRECTORY_SEPARATOR . $item;
					if (is_file($file)) {
						$messagesSuccess[] = 'file "' . $file . '" already exist';
					} else if (touch($file)) {
						$messagesSuccess[] = 'file "' . $file . '" created';
					} else {
						$messagesFail[] = 'file"' . $file . '" does not created';
					}
				} else if ($section == 'links') {
					$target = $path . DIRECTORY_SEPARATOR . (explode(':', $item)[1]);
					$link = $path . DIRECTORY_SEPARATOR . (explode(':', $item)[0]);
					if (is_link($link)) {
						$messagesSuccess[] = 'link "' . $link . '" already exist';
					} else if (symlink($target, $link)) {
						$messagesSuccess[] = 'link "' . $link . '" created';
					} else {
						$messagesFail[] = 'link ' . $link . '" does not created';
					}
				}
			}
		}

		$configFile = $path . DIRECTORY_SEPARATOR . $this->mapping['config-file'];
		if ($this->isEmptyConfigFile($configFile)) {
			try {
				$config = $this->calcConfigExternal($this->config, $this->configInternal);
				$this->writeConfigFile($configFile, $config);
				$messagesSuccess[] = 'config file "' . $configFile . '" are wrote';
			} catch (\Exception $e) {
				$messagesFail[] = $e->getMessage();
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

		$pathsClean = $this->flatPaths($path, $this->structure);
		$pathsDirty = $this->flatPaths($path, $this->scanStructure($path));
		$pathsDiff = array_diff($pathsDirty, $pathsClean);

		foreach ($pathsDiff as $item) {
			if (empty($item)) continue;
			if (is_link($item)) unlink($item);
			if (is_file($item)) unlink($item);
			if (is_dir($item)) rmdir($item);
		}

		$payload->setType(Payload::STRUCTURE_CLEAN_SUCCESS);
		$payload->setMessage(array_merge(['cleaned items:'], $pathsDiff));
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

		$releaseDir = $path . DIRECTORY_SEPARATOR . $this->mapping['releases-dir'] . DIRECTORY_SEPARATOR . $release;
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

		$releaseDir = realpath($path) . DIRECTORY_SEPARATOR . $this->mapping['releases-dir'] . DIRECTORY_SEPARATOR . $release;

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

		$proccess = new Process('rm -r ' . realpath($path) . DIRECTORY_SEPARATOR . $this->mapping['releases-dir'] . DIRECTORY_SEPARATOR . $release);
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

		$dirs = glob(realpath($path) . DIRECTORY_SEPARATOR . $this->mapping['releases-dir'] . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);

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

		$dirs = glob(realpath($path) . DIRECTORY_SEPARATOR . $this->mapping['releases-dir'] . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);

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

		$link = realpath($path) . DIRECTORY_SEPARATOR . $this->mapping['current-link'];

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

		$releaseDir = realpath($path) . DIRECTORY_SEPARATOR . $this->mapping['releases-dir'] . DIRECTORY_SEPARATOR . $release;
		$currentLink = realpath($path) . DIRECTORY_SEPARATOR . $this->mapping['current-link'];

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

		$releases = glob(realpath($path) . DIRECTORY_SEPARATOR . $this->mapping['releases-dir'] . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);

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