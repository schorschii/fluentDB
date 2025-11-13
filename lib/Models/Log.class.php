<?php

namespace Models;

class Log {

	public $id;
	public $timestamp;
	public $level;
	public $host;
	public $user;
	public $object_id;
	public $action;
	public $data;

	// constants
	public const DEFAULT_VIEW_LIMIT = 80;

	public const LEVEL_DEBUG   = 0;
	public const LEVEL_INFO    = 1;
	public const LEVEL_WARNING = 2;
	public const LEVEL_ERROR   = 3;

	public const LEVELS = [
		self::LEVEL_DEBUG 	=> '0 - Debug',
		self::LEVEL_INFO 	=> '1 - Info',
		self::LEVEL_WARNING => '2 - Warning',
		self::LEVEL_ERROR 	=> '3 - Error',
		4 					=> '4 - No Logging',
	];

	public const ACTION_CLIENT_API_RAW = 'fluentdb.client.api.rawrequest';
	public const ACTION_CLIENT_API     = 'fluentdb.client.api';
	public const ACTION_CLIENT_WEB     = 'fluentdb.client.web';

}
