<?php

namespace Controllers\PublicSite;

class BotController extends \Controllers\PublicSite\PublicBaseController {

	public function post() {

		$resp = '';

		if ($this->routeargs['command'] === 'now') {

			$sessions = $this->app->db->queryAllRows('SELECT * FROM sessions WHERE start_time < NOW() AND end_time > NOW()');
			if (!count($sessions)) {
				$resp = "There are no sessions happening at the moment";
			} else {
				$resp = 'Sessions in progress:';
				foreach ($sessions as $session) {
					$resp .= "\n    •  `".$session['start_time']->format('H:i')."`  *" . $session['name'] . "*";
					if ($session['room']) {
						$resp .= " (in " . $session['room'] . ")";
					}
				}
			}

		} elseif ($this->routeargs['command'] === 'next') {

			$timeslot = $this->app->db->querySingle('SELECT start_time FROM sessions WHERE start_time > NOW() ORDER BY start_time LIMIT 1');
			$sessions = $this->app->db->queryAllRows('SELECT * FROM sessions WHERE start_time = %s|date', $timeslot);
			if (!count($sessions)) {
				$resp = "There are no upcoming sessions";
			} else {
				$resp = 'Coming up:';
				foreach ($sessions as $session) {
					$resp .= "\n    •  `".$session['start_time']->format('H:i')."`  *" . $session['name'] . "*";
					if ($session['room']) {
						$resp .= " (in " . $session['room'] . ")";
					}
				}
			}

		} elseif ($this->routeargs['command'] === 'feedback') {

			if ($this->req->getPost('text')) {
				$this->app->db->query('INSERT INTO feedback SET {user_name}, {channel_name}, {text}, created_at=NOW()', $this->req->getPost());
				$resp = 'Thank you, your feedback has been recorded';
			} else {
				$resp = 'Usage: `/feedback <feedback text>`';
			}
		}

		if ($resp) $this->resp->setContent($resp);
	}

}
