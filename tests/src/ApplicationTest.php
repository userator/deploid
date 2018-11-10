<?php

namespace Deploid;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2018-10-25 at 19:53:51.
 */
class ApplicationTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Application
	 */
	protected $object;

	/**
	 * @var string
	 */
	protected $path;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		$this->object = new Application;
		$this->path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . strtolower(__NAMESPACE__);
		$this->createWorkDir($this->path);
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown() {
		$this->object = null;
		$this->path = null;
		$this->removeWorkDir($this->path);
	}

	/**
	 * @param string $path
	 */
	private function createWorkDir($path) {
		$process = new \Symfony\Component\Process\Process('mkdir ' . $path);
		$process->run();
	}

	/**
	 * @param string $path
	 */
	private function removeWorkDir($path) {
		$process = new \Symfony\Component\Process\Process('rm -rf ' . $path);
		$process->run();
	}

	/**
	 * @covers Deploid\Application::getLogger
	 * @todo   Implement testGetLogger().
	 */
	public function testGetLogger() {
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
				'This test has not been implemented yet.'
		);
	}

	/**
	 * @covers Deploid\Application::setLogger
	 * @todo   Implement testSetLogger().
	 */
	public function testSetLogger() {
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
				'This test has not been implemented yet.'
		);
	}

	/**
	 * @covers Deploid\Application::deploidStructureValidate
	 */
	public function testDeploidStructureValidate() {
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
				'This test has not been implemented yet.'
		);
	}

	/**
	 * @covers Deploid\Application::deploidStructureInit
	 */
	public function testDeploidStructureInit() {
		$payload = $this->object->deploidStructureInit($this->path);

		$this->assertEquals(0, $payload->getCode());
		$this->assertDirectoryExists($this->path);
		$this->assertDirectoryExists($this->path . DIRECTORY_SEPARATOR . 'releases');
		$this->assertDirectoryExists($this->path . DIRECTORY_SEPARATOR . 'shared');
		$this->assertFileExists($this->path . DIRECTORY_SEPARATOR . 'deploid.log');

		return $payload;
	}

	/**
	 * @covers Deploid\Application::deploidStructureClean
	 */
	public function testDeploidStructureClean() {
		$structure = [
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
			]
		];
		$this->object->makeStructure($this->path, $structure);

		$pathsInit = glob($this->path . DIRECTORY_SEPARATOR . '*');

		$needlessDir = $this->path . DIRECTORY_SEPARATOR . 'needless';
		$isMkdir = mkdir($needlessDir);

		$needlessFile = $this->path . DIRECTORY_SEPARATOR . 'needless.log';
		$isTouch = touch($needlessFile);

		$pathsBad = glob($this->path . DIRECTORY_SEPARATOR . '*');
		$payload = $this->object->deploidStructureClean($this->path);
		$pathsGood = glob($this->path . DIRECTORY_SEPARATOR . '*');

		$this->assertNotFalse($pathsInit);
		$this->assertNotFalse($isMkdir, $needlessDir);
		$this->assertNotFalse($isTouch, $needlessFile);
		$this->assertNotFalse($pathsBad);
		$this->assertEquals(0, $payload->getCode());
		$this->assertNotFalse($pathsGood);
		$this->assertEquals($pathsInit, $pathsGood);
		$this->assertNotEquals($pathsGood, $pathsBad);
		$this->assertDirectoryNotExists($needlessDir);
		$this->assertFileNotExists($needlessFile);

		return $payload;
	}

	/**
	 * @covers Deploid\Application::deploidReleaseExist
	 */
	public function testDeploidReleaseExist() {
		$releasesDir = 'releases';
		$releaseNameExist = date($this->object->getReleaseNameFormat());
		$releaseNameNotExist = date($this->object->getReleaseNameFormat(), time() + 3600);

		$structure = [
			'dirs' => [
				$releasesDir,
				$releasesDir . DIRECTORY_SEPARATOR . $releaseNameExist,
			],
		];
		$this->object->makeStructure($this->path, $structure);

		$payloadSuccess = $this->object->deploidReleaseExist($releaseNameExist, $this->path);
		$payloadFail = $this->object->deploidReleaseExist($releaseNameNotExist, $this->path);

		$this->assertEquals(0, $payloadSuccess->getCode());
		$this->assertDirectoryExists($this->path . DIRECTORY_SEPARATOR . $releasesDir . DIRECTORY_SEPARATOR . $releaseNameExist);

		$this->assertNotEquals(0, $payloadFail->getCode());
		$this->assertDirectoryNotExists($this->path . DIRECTORY_SEPARATOR . $releasesDir . DIRECTORY_SEPARATOR . $releaseNameNotExist);
	}

	/**
	 * @covers Deploid\Application::deploidReleaseCreate
	 */
	public function testDeploidReleaseCreate() {
		$releasesDir = 'releases';
		$releaseName = date($this->object->getReleaseNameFormat());

		$structure = [
			'dirs' => [
				$releasesDir,
			],
		];
		$this->object->makeStructure($this->path, $structure);

		$payload = $this->object->deploidReleaseCreate($releaseName, $this->path);

		$this->assertEquals(0, $payload->getCode());
		$this->assertDirectoryExists($this->path . DIRECTORY_SEPARATOR . $releasesDir . DIRECTORY_SEPARATOR . $releaseName);

		return $payload;
	}

	/**
	 * @covers Deploid\Application::deploidReleaseRemove
	 */
	public function testDeploidReleaseRemove() {
		$releasesDir = 'releases';
		$releaseName = date($this->object->getReleaseNameFormat());

		$structure = [
			'dirs' => [
				$releasesDir,
				$releasesDir . DIRECTORY_SEPARATOR . $releaseName,
			],
		];
		$this->object->makeStructure($this->path, $structure);

		$payload = $this->object->deploidReleaseRemove($releaseName, $this->path);

		$this->assertEquals(0, $payload->getCode());
		$this->assertDirectoryNotExists($this->path . DIRECTORY_SEPARATOR . $releasesDir . DIRECTORY_SEPARATOR . $releaseName);

		return $payload;
	}

	/**
	 * @covers Deploid\Application::deploidReleaseList
	 */
	public function testDeploidReleaseList() {
		$releasesDir = 'releases';
		$releaseName = date($this->object->getReleaseNameFormat());

		$structure = [
			'dirs' => [
				$releasesDir,
				$releasesDir . DIRECTORY_SEPARATOR . $releaseName,
			],
		];
		$this->object->makeStructure($this->path, $structure);

		$payload = $this->object->deploidReleaseList($this->path);

		$this->assertEquals(0, $payload->getCode());
		$this->assertEquals([$releaseName], $payload->getMessage());

		return $payload;
	}

	/**
	 * @covers Deploid\Application::deploidReleaseLatest
	 */
	public function testDeploidReleaseLatest() {
		$releasesDir = 'releases';
		$releaseNameFirst = date($this->object->getReleaseNameFormat());
		$releaseNameLast = date($this->object->getReleaseNameFormat(), time() + 3600);

		$structure = [
			'dirs' => [
				$releasesDir,
				$releasesDir . DIRECTORY_SEPARATOR . $releaseNameFirst,
				$releasesDir . DIRECTORY_SEPARATOR . $releaseNameLast,
			],
		];
		$this->object->makeStructure($this->path, $structure);

		$payload = $this->object->deploidReleaseLatest($this->path);

		$this->assertEquals(0, $payload->getCode());
		$this->assertEquals($releaseNameLast, $payload->getMessage());

		return $payload;
	}

	/**
	 * @covers Deploid\Application::deploidReleaseCurrent
	 */
	public function testDeploidReleaseCurrent() {
		$releasesDir = 'releases';
		$releaseName = date($this->object->getReleaseNameFormat());
		$currentLink = 'current';

		$structure = [
			'dirs' => [
				$releasesDir,
				$releasesDir . DIRECTORY_SEPARATOR . $releaseName,
			],
			'links' => [
				$currentLink . ':' . $releasesDir . DIRECTORY_SEPARATOR . $releaseName,
			]
		];
		$this->object->makeStructure($this->path, $structure);

		$payload = $this->object->deploidReleaseCurrent($this->path);

		$this->assertEquals(0, $payload->getCode());
		$this->assertEquals($releaseName, $payload->getMessage());
		$this->assertFileExists($this->path . DIRECTORY_SEPARATOR . $currentLink);
		$this->assertDirectoryExists($this->path . DIRECTORY_SEPARATOR . $releasesDir . DIRECTORY_SEPARATOR . $releaseName);

		return $payload;
	}

	/**
	 * @covers Deploid\Application::deploidReleaseSetup
	 */
	public function testDeploidReleaseSetup() {
		$releasesDir = 'releases';
		$releaseNameFirst = date($this->object->getReleaseNameFormat());
		$releaseNameLast = date($this->object->getReleaseNameFormat(), time() + 3600);
		$currentLink = 'current';

		$structure = [
			'dirs' => [
				$releasesDir,
				$releasesDir . DIRECTORY_SEPARATOR . $releaseNameFirst,
				$releasesDir . DIRECTORY_SEPARATOR . $releaseNameLast,
			],
			'links' => [
				$currentLink . ':' . $releasesDir . DIRECTORY_SEPARATOR . $releaseNameFirst,
			]
		];
		$this->object->makeStructure($this->path, $structure);

		$payload = $this->object->deploidReleaseSetup($releaseNameLast, $this->path);

		$this->assertEquals(0, $payload->getCode());
		$this->assertContains($releaseNameLast, $payload->getMessage());
		$this->assertFileExists($this->path . DIRECTORY_SEPARATOR . $currentLink);
		$this->assertDirectoryExists($this->path . DIRECTORY_SEPARATOR . $releasesDir . DIRECTORY_SEPARATOR . $releaseNameLast);
		$this->assertEquals(realpath($this->path . DIRECTORY_SEPARATOR . $releasesDir . DIRECTORY_SEPARATOR . $releaseNameLast), realpath(readlink($this->path . DIRECTORY_SEPARATOR . $currentLink)));

		return $payload;
	}

	/**
	 * @covers Deploid\Application::deploidReleaseRotate
	 */
	public function testDeploidReleaseRotate() {
		$releasesDir = 'releases';
		$releaseNameFirst = date($this->object->getReleaseNameFormat());
		$releaseNameLast = date($this->object->getReleaseNameFormat(), time() + 3600);
		$quantity = 1;


		$structure = [
			'dirs' => [
				$releasesDir,
				$releasesDir . DIRECTORY_SEPARATOR . $releaseNameFirst,
				$releasesDir . DIRECTORY_SEPARATOR . $releaseNameLast,
			],
		];
		$this->object->makeStructure($this->path, $structure);

		$payload = $this->object->deploidReleaseRotate($quantity, $this->path);

		$this->assertEquals(0, $payload->getCode());
		$this->assertDirectoryNotExists($this->path . DIRECTORY_SEPARATOR . $releasesDir . DIRECTORY_SEPARATOR . $releaseNameFirst);
		$this->assertDirectoryExists($this->path . DIRECTORY_SEPARATOR . $releasesDir . DIRECTORY_SEPARATOR . $releaseNameLast);
		$this->assertCount($quantity, glob($this->path . DIRECTORY_SEPARATOR . $releasesDir));

		return $payload;
	}

	/**
	 * @covers Deploid\Application::absolutePath
	 * @todo   Implement testAbsolutePath().
	 */
	public function testAbsolutePath() {
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
				'This test has not been implemented yet.'
		);
	}

}