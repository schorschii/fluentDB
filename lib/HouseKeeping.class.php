<?php

class HouseKeeping {

	// this script is intended to be called periodically every 10 minutes via cron

	private /*DatabaseController*/ $db;
	private /*ExtensionController*/ $ext;
	private /*bool*/ $debug;

	function __construct(DatabaseController $db, ExtensionController $ext, bool $debug=false) {
		$this->db = $db;
		$this->ext = $ext;
		$this->debug = $debug;
	}

	public function cleanup() {
		// core housekeeping
		$this->logHouseKeeping();

		// extension housekeeping
		foreach($this->ext->getAggregatedConf('housekeeping-function') as $func) {
			if($this->debug) echo('Executing extension function: '.$func."\n");
			call_user_func($func, $this->db);
		}

		if($this->debug) echo('Housekeeping Done.'."\n");
	}

	private function logHouseKeeping() {
		$purgeLogsAfter = $this->db->settings->get('purge-logs-after');
		$result = $this->db->deleteLogEntryOlderThan($purgeLogsAfter);
		if($this->debug) echo('Purged '.intval($result).' log entries older than '.intval($purgeLogsAfter).' seconds'."\n");
	}
}
