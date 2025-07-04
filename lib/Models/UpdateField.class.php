<?php

namespace Models;

class UpdateField {

	public $category_id;
	public $category_field_id;
	public $category_set_id;
	public $value;

	function __construct($category_id, $category_field_id, $category_set_id, $value) {
		$this->category_id = $category_id;
		$this->category_field_id = $category_field_id;
		$this->category_set_id = $category_set_id;
		$this->value = $value;
	}

}
