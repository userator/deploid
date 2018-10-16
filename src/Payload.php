<?php

namespace Deploid;

class Payload {

	const STRUCTURE_VALIDATE_SUCCESS = 'structure_validate_success';
	const STRUCTURE_VALIDATE_FAIL = 'structure_validate_fail';
	const STRUCTURE_INIT_SUCCESS = 'structure_init_success';
	const STRUCTURE_INIT_FAIL = 'structure_init_fail';
	const RELEASE_EXIST_SUCCESS = 'release_exist_success';
	const RELEASE_EXIST_FAIL = 'release_exist_fail';
	const RELEASE_CREATE_SUCCESS = 'release_create_success';
	const RELEASE_CREATE_FAIL = 'release_create_fail';
	const RELEASE_REMOVE_SUCCESS = 'release_remove_success';
	const RELEASE_REMOVE_FAIL = 'release_remove_fail';
	const RELEASE_CURRENT_SUCCESS = 'release_current_success';
	const RELEASE_CURRENT_FAIL = 'release_current_fail';

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