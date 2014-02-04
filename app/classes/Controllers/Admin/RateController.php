<?php

namespace Controllers\Admin;

class RateController extends \Controllers\Admin\AdminBaseController {

	public function get() {

		// Get the next event
		$event = $this->app->db->queryRow('SELECT * FROM events WHERE end_time > NOW() ORDER BY start_time ASC LIMIT 1');

		// Fetch proposals waiting to be rated
		$sessions = $this->app->db->queryAllRows('SELECT * FROM sessions WHERE event_id=%d AND type=%s ORDER BY start_time', $event['id'], 'Session');
		foreach ($sessions as &$session) {
			$session['stats'] = $this->app->db->queryRow('SELECT SUM(IF(ticket_type IS NOT NULL, 1, 0)) as attending, SUM(IF(invite_date_sent IS NOT NULL AND ticket_type IS NULL, 1, 0)) as invited, SUM(IF(ticket_type IS NULL AND invite_date_sent IS NULL, 1, 0)) as waitlist, AVG(rating) as avgrating FROM attendance a INNER JOIN participation p ON a.person_id=p.person_id WHERE p.session_id=%d', $session['id']);
			$session['proposals'] = $this->app->db->query('SELECT pe.id as person_id, pe.email, pe.org, pa.proposal FROM people pe INNER JOIN participation pa ON pe.id=pa.person_id WHERE pa.session_id=%d AND role=%s AND rating IS NULL', $session['id'], 'Delegate');
		}

		$this->addViewData(array(
			'event' => $event,
			'sessions' => $sessions,
		));
		$this->renderView('admin/rate');
	}

	public function post() {
		$this->app->db->query('START TRANSACTION');

		$counts = array('done'=>0, 'blank'=>0);
		foreach ($this->req->getPost() as $key => $rating) {

			if (!preg_match('/^rating_(\d+)_(\d+)$/i', $key, $m)) continue;

			if (!is_numeric($rating)) {
				$counts['blank']++;
				continue;
			}

			$personid = $m[1];
			$sessionid = $m[2];

			$this->app->db->query("UPDATE participation SET rating=%d, rated_by=%s, rating_date=NOW() WHERE session_id=%d AND person_id=%d", $rating, $this->user['email'], $sessionid, $personid);
			$counts['done']++;
		}

		$this->app->db->query("COMMIT");
		if ($counts['done']) $this->alert('info', 'Saved new proposal ratings ('.$counts['done'].')');
		if ($counts['blank']) $this->alert('warning', 'No rating provided for proposal ('.$counts['blank'].')');
		$this->resp->redirect('/admin/rate');
	}
}
