<?php

namespace Deploid;

use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Process\Process;

class Application extends ConsoleApplication {

	/**
	 * @param string $path
	 * @return \Deploid\Payload
	 */
	public function deploidStructureValidate($path) {
		$proccess = new Process([
			'test -d ' . rtrim($path, '\\/') . DIRECTORY_SEPARATOR . 'releases',
			'test -d ' . rtrim($path, '\\/') . DIRECTORY_SEPARATOR . 'shared',
			'test -f ' . rtrim($path, '\\/') . DIRECTORY_SEPARATOR . 'deploid.log',
		]);

		$proccess->run();

		$payload = new Payload();

		if (!$proccess->isSuccessful()) {
			$payload->setType(Payload::STRUCTURE_VALIDATE_FAIL);
			$payload->setMessage('structure in path "' . $path . '" not valid');
			$payload->setCode(255);
			return $payload;
		}

		$payload->setType(Payload::STRUCTURE_VALIDATE_SUCCESS);
		$payload->setMessage('structure in path "' . $path . '" valid');
		$payload->setCode(0);
		return $payload;
	}

	/**
	 * @param string $path
	 * @return \Deploid\Payload
	 */
	public function deploidStructureInit($path) {
		$proccess = new Process([
			'mkdir -r ' . rtrim($path, '\\/') . DIRECTORY_SEPARATOR . 'releases',
			'mkdir -r ' . rtrim($path, '\\/') . DIRECTORY_SEPARATOR . 'shared',
			'touch ' . rtrim($path, '\\/') . DIRECTORY_SEPARATOR . 'deploid.log',
		]);

		$proccess->run();

		$payload = new Payload();

		if (!$proccess->isSuccessful()) {
			$payload->setType(Payload::STRUCTURE_INIT_FAIL);
			$payload->setMessage($proccess->getErrorOutput());
			$payload->setCode($proccess->getExitCode());
			return $payload;
		}

		$payload->setType(Payload::STRUCTURE_INIT_SUCCESS);
		$payload->setMessage('structure inited');
		$payload->setCode(0);
		return $payload;
	}

	/**
	 * @param string $release
	 * @param string $path
	 * @return \Deploid\Payload
	 */
	public function deploidReleaseExist($release, $path) {
		$proccess = new Process('test -d ' . rtrim($path, '\\/') . DIRECTORY_SEPARATOR . $release);
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
		$proccess = new Process('mkdir -r ' . rtrim($path, '\\/') . DIRECTORY_SEPARATOR . $release);
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
		$proccess = new Process('rm -r ' . rtrim($path, '\\/') . DIRECTORY_SEPARATOR . $release);
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
	 * @param string $release
	 * @param string $path
	 * @return \Deploid\Payload
	 */
	public function deploidReleaseCurrent($release, $path) {
		$releaseDir = rtrim($path, '\\/') . DIRECTORY_SEPARATOR . 'releases' . DIRECTORY_SEPARATOR . $release;
		$currentDir = rtrim($path, '\\/') . DIRECTORY_SEPARATOR . 'current';

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

}