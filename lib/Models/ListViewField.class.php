<?php

namespace Models;

class ListViewField {

	public $list_view_id;
	public $category_field_id;
	public $order;

	public $category_id;
	public $title;

	static function initWithValues($category_field_id=null, $category_id=null, $title=null) {
		$o = new self();
		$o->category_field_id = $category_field_id;
		$o->category_id = $category_id;
		$o->title = $title;
		$o->order = 0;
		return $o;
	}

}
