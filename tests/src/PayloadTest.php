<?php

namespace Deploid;

class PayloadTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Payload
	 */
	protected $payload;

	protected function setUp() {
		$this->payload = new Payload();
	}

	protected function tearDown() {
		$this->payload = null;
	}

	/**
	 * @covers Deploid\Payload::create
	 */
	public function testCreate() {
		$type = 'type';
		$code = 255;
		$message = 'test message';

		$payload = $this->payload->create($type, $message, $code);

		$this->assertEquals($type, $payload->getType());
		$this->assertEquals($message, $payload->getMessage());
		$this->assertEquals($code, $payload->getCode());
		$this->assertInstanceOf(Payload::class, $payload);
	}

	/**
	 * @covers Deploid\Payload::toArray
	 */
	public function testToArray() {
		$struct = [
			'type' => 'type',
			'message' => 'test message',
			'code' => 255,
		];

		$payload = clone $this->payload;
		$payload->setType($struct['type']);
		$payload->setMessage($struct['message']);
		$payload->setCode($struct['code']);

		$this->assertEquals($struct['type'], $payload->getType());
		$this->assertEquals($struct['message'], $payload->getMessage());
		$this->assertEquals($struct['code'], $payload->getCode());
		$this->assertEquals($struct, $payload->toArray());
	}

}