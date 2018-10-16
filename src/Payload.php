<?php

namespace Deploid;

class Payload {

	/** @var string Тип */
	private $type;

	/** @var string Сообщение */
	private $message;

	public function getType() {
		return $this->type;
	}

	public function getMessage() {
		return $this->message;
	}

	public function setType($type) {
		$this->type = $type;
		return $this;
	}

	public function setMessage($message) {
		$this->message = $message;
		return $this;
	}

}