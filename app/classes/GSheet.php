<?php

class GSheet extends Coseva\CSV {

	function __construct($key, $sheetidx=0) {
		$gsheetreq = new HTTP\HTTPRequest('https://docs.google.com/spreadsheets/d/'.$key.'/pub&single=true&gid='.$sheetidx.'&output=csv');
		$gsheetreq->setFollowLocation(true);
		try {
			$gsheetresp = $gsheetreq->send();
			$csvdata = $gsheetresp->getBody();
		} catch (\Exception $e) {
			$csvdata = '';
		}
		$filename = tempnam('/tmp', 'edgecsv');
		file_put_contents($filename, $csvdata);
		parent::__construct($filename);
		$this->parse();
	}
}
