<?php

namespace Controllers\PublicSite;

class BotController extends \Controllers\PublicSite\PublicBaseController {

	public function post() {

		$resp = '';

		if ($this->routeargs['command'] === 'now') {

			$sessions = $this->app->db->queryAllRows('SELECT s.start_time, s.name, s.room, s.type, e.time_zone FROM sessions s INNER JOIN events e ON s.event_id=e.id WHERE s.start_time < NOW() AND s.end_time > NOW()');
			if (!count($sessions)) {
				$resp = "There are no sessions happening at the moment";
			} else {
				$resp = 'Sessions in progress:';
				foreach ($sessions as $session) {
					$tz = new \DateTimeZone($session['time_zone']);
					$resp .= "\n    •  `".$session['start_time']->setTimeZone($tz)->format('H:i')."`  *" . $session['name'] . "*";
					if ($session['room']) {
						$resp .= " (in " . $session['room'] . ")";
					}
				}
			}

		} elseif ($this->routeargs['command'] === 'next') {

			$timeslot = $this->app->db->querySingle('SELECT start_time FROM sessions WHERE start_time > NOW() AND type in %s|list ORDER BY start_time LIMIT 1', array('Session', 'Breakout', 'Other'));
			$sessions = $this->app->db->queryAllRows('SELECT s.start_time, s.name, s.room, s.type, e.time_zone FROM sessions s INNER JOIN events e ON s.event_id=e.id WHERE s.start_time = %s|date', $timeslot);
			if (!count($sessions)) {
				$resp = "There are no upcoming sessions";
			} else {
				$resp = 'Coming up:';
				foreach ($sessions as $session) {
					$tz = new \DateTimeZone($session['time_zone']);
					$resp .= "\n    •  `".$session['start_time']->setTimeZone($tz)->format('H:i')."`  *" . $session['name'] . "*";
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
				$resp = 'Usage: `/edge-feedback <feedback text>`';
			}
		}

		if ($resp) $this->resp->setContent($resp);
	}

}
