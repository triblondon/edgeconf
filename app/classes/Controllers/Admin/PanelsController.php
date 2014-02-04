<?php

namespace Controllers\Admin;

class PanelsController extends \Controllers\Admin\AdminBaseController {

	public function get() {

		// Get the next event
		$event = $this->app->db->queryRow('SELECT * FROM events WHERE end_time > NOW() ORDER BY start_time ASC LIMIT 1');

		$sessions = $this->app->db->queryAllRows('SELECT * FROM sessions WHERE event_id=%d AND type=%s ORDER BY start_time', $event['id'], 'Session');
		foreach ($sessions as &$session) {
			$session['people'] = $this->app->db->queryAllRows('SELECT pe.id, pe.given_name, pe.family_name, pe.email, pe.org, pe.bio, pe.travel_origin, pa.role, pa.panel_status, a.ticket_type FROM people pe INNER JOIN participation pa ON pe.id=pa.person_id INNER JOIN attendance a ON a.person_id=pe.id AND a.event_id=%d WHERE pa.session_id=%d AND role <> %s ORDER BY (panel_status=%s) DESC, (panel_status=%s) DESC', $event['id'], $session['id'], 'Delegate', 'Confirmed', 'Interested');
		}

		$this->addViewData(array(
			'event' => $event,
			'sessions' => $sessions,
		));
		$this->renderView('admin/panels');
	}
}
