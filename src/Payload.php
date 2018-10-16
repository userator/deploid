<?php

namespace Deploid;

class Payload {

	/** @var integer Код процесса */
	private $code;

	/** @var string Тип */
	private $type;

	/** @var string Сообщение */
	private $message;

	public function __construct($type = null, $message = null, $code = null) {
		$this->type = $type;
		$this->message = $message;
		$this->code = $code;
	}

	/* mutators */

	public function getCode() {
		return $this->code;
	}

	public function getType() {
		return $this->type;
	}

	public function getMessage() {
		return $this->message;
	}

	public function setCode($code) {
		$this->code = $code;
		return $this;
	}

	public function setType($type) {
		$this->type = $type;
		return $this;
	}

	public function setMessage($message) {
		$this->message = $message;
		return $this;
	}

	/* tools */

	public function toArray() {
		return [
			'type' => $this->type,
			'message' => $this->message,
			'code' => $this->code,
		];
	}

}