<?php

namespace Controllers\Admin;

class ExportController extends \Controllers\Admin\AdminBaseController {

	public function get() {

		// Get the next event
		$event = $this->app->db->queryRow('SELECT * FROM events WHERE end_time > NOW() ORDER BY start_time ASC LIMIT 1');

		$data = array();

		if ($this->routeargs['export'] == 'panels') {
			$sessions = $this->app->db->queryAllRows('SELECT * FROM sessions WHERE event_id=%d AND type=%s', $event['id'], 'session');
			foreach ($sessions as $session) {
				$data[self::slugify($session['name'])] = array('panelists'=>array(), 'questions'=>array());
				$panelists = $this->app->db->queryAllRows('SELECT pe.twitter_username, pe.family_name, pe.given_name, pe.org, pa.role FROM participation pa INNER JOIN people pe ON pa.person_id=pe.id WHERE pa.session_id=%d AND role != %s AND panel_status=%s', $session['id'], 'Delegate', 'Confirmed');
				foreach ($panelists as $panelist) {
					$data[self::slugify($session['name'])]['panelists'][] = array(
						'Surname' => $panelist['family_name'],
						'FirstName' => $panelist['given_name'],
						'mod' => ($panelist['role'] == 'Moderator'),
						'pic' => 'http://edgeconf.com/images/heads/'.self::slugify($panelist['given_name'].'-'.$panelist['family_name'], '-').'.jpg',
						'twitter' => $panelist['twitter_username'],
						'org' => $panelist['org']
					);
				}
			}
			$gsheet = new \GSheet('0AqIP_kC-Q_iIdE9oWU9xb0I5dF82RHhiOTJuQk1GNWc', 4);
			foreach ($gsheet as $row) {
				if (is_numeric($row[1]) and $row[2]) {
					$data[self::slugify($row[0])]['questions'][] = $row[2];
				}
			}
		} elseif ($this->routeargs['export'] == 'attendees') {
			$attendees = $this->app->db->queryAllRows('SELECT pe.id, pe.family_name, pe.given_name, pe.email, pe.org, a.ticket_type, a.ticket_date FROM people pe INNER JOIN attendance a ON a.person_id=pe.id WHERE a.event_id=%d AND ticket_type IS NOT NULL', $event['id']);
			foreach ($attendees as $pe) {
				$pe['ticket_date'] = new \DateTime($pe['ticket_date']);
				$data[] = array(
					'Attendee no.' => $pe['id'],
					'Date' => $pe['ticket_date']->format('j M Y'),
					'Surname' => $pe['family_name'],
					'FirstName' => $pe['given_name'],
					'Email' => $pe['email'],
					'Ticket Type' => $pe['ticket_type'],
					'org' => $pe['org'],
					'Sessions of interest' => $this->app->db->queryList('SELECT s.name FROM sessions s INNER JOIN participation p ON s.id=p.session_id WHERE p.person_id=%d AND s.event_id=%d', $pe['id'], $event['id'])
				);
			}
		}

		$this->resp->setJSON($data);
	}

	private static function slugify($str, $sep='_') {
		return str_replace(' ', $sep, strtolower($str));
	}
}
