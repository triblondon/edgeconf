<?php

namespace Controllers\PublicSite;

class BotController extends \Controllers\PublicSite\PublicBaseController {

	public function post() {

		$resp = '';
		$qry = strtolower(preg_replace('/^\@?edgebot\s*\:?\s+/i', '', $this->req->getPost('text')));

		if ($qry === 'now') {

			$sessions = $this->app->db->queryAllRows('SELECT * FROM sessions WHERE start_time < NOW() AND end_time > NOW()');
			if (!count($sessions)) {
				$resp = "There are no sessions happening at the moment";
			} else {
				$resp = 'Sessions in progress:';
				foreach ($sessions as $session) {
					$resp .= "\n    • `".$session['start_time']->format('H:i')."` *" . $session['name'] . "*";
					if ($session['room']) {
						$resp .= " (in " . $session['room'] . ")";
					}
				}
			}


		} else {

			$help = array(
				'now' => "What sessions are happening now",
				'next' => "What sessions are coming up next",
				'feedback <text>' => "Log feedback"
			);
			$resp = 'Usage:';
			foreach ($help as $term => $helpstr) $resp .= '\n    • `'.$term.'` - '.$helpstr;
		}

		if ($resp) {
			$this->resp->setJSON(array("text"=>$resp));
		}
	}

}
