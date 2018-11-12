<?php

namespace Deploid;

class Payload {

	const STRUCTURE_VALIDATE_SUCCESS = 'structure_validate_success';
	const STRUCTURE_VALIDATE_FAIL = 'structure_validate_fail';
	const STRUCTURE_INIT_SUCCESS = 'structure_init_success';
	const STRUCTURE_INIT_FAIL = 'structure_init_fail';
	const STRUCTURE_CLEAN_SUCCESS = 'structure_clean_success';
	const STRUCTURE_CLEAN_FAIL = 'structure_clean_fail';
	const RELEASE_EXIST_SUCCESS = 'release_exist_success';
	const RELEASE_EXIST_FAIL = 'release_exist_fail';
	const RELEASE_CREATE_SUCCESS = 'release_create_success';
	const RELEASE_CREATE_FAIL = 'release_create_fail';
	const RELEASE_REMOVE_SUCCESS = 'release_remove_success';
	const RELEASE_REMOVE_FAIL = 'release_remove_fail';
	const RELEASE_CURRENT_SUCCESS = 'release_current_success';
	const RELEASE_CURRENT_FAIL = 'release_current_fail';
	const RELEASE_SETUP_SUCCESS = 'release_setup_success';
	const RELEASE_SETUP_FAIL = 'release_setup_fail';
	const RELEASE_LIST_SUCCESS = 'release_list_success';
	const RELEASE_LIST_FAIL = 'release_list_fail';
	const RELEASE_ROTATE_SUCCESS = 'release_rotate_success';
	const RELEASE_ROTATE_FAIL = 'release_rotate_fail';
	const RELEASE_LATEST_SUCCESS = 'release_latest_success';
	const RELEASE_LATEST_FAIL = 'release_latest_fail';

	/** @var integer Код процесса */
	private $code;

	/** @var string Тип */
	private $type;

	/** @var string|array Сообщение */
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
	}

	public function setType($type) {
		$this->type = $type;
	}

	public function setMessage($message) {
		$this->message = $message;
	}

	/* tools */

	static public function create($type = null, $message = null, $code = null) {
		return new static($type, $message, $code);
	}

	public function toArray() {
		return [
			'type' => $this->type,
			'message' => $this->message,
			'code' => $this->code,
		];
	}

}