<?php

namespace Models;

class ObjCategorySet {

	public $id;
	public $object_id;
	public $category_id;
	public $status;

	static function initWithValues($id) {
		$o = new self();
		$o->id = $id;
		return $o;
	}

}
