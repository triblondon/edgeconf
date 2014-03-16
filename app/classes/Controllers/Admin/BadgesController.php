<?php

namespace Controllers\Admin;

class BadgesController extends \Controllers\Admin\AdminBaseController {

	/**
	 * @todo Remove hardcoded identifiers.
	 * Party-Sessions are handled separatly as they generate an Exception in case
	 * someone is assigned to the party and any other sessions.
	 */

	private $partySessions = array(29, 37);

	public function get() {

		// Get the next event
		$event = $this->app->db->queryRow('SELECT * FROM events WHERE end_time > NOW() ORDER BY start_time ASC LIMIT 1');

		$attendees = $this->app->db->queryAllRows('SELECT pe.* FROM people pe INNER JOIN attendance a ON a.person_id=pe.id WHERE a.event_id=%d AND ticket_type IS NOT NULL', $event['id']);
		foreach ($attendees as &$attendee) {
			$attendee['interests'] = $this->app->db->queryList('SELECT s.id FROM sessions s INNER JOIN participation p ON s.id=p.session_id WHERE p.person_id=%d AND s.event_id=%d', $attendee['id'], $event['id']);

			if (count($attendee['interests']) > 1) {
				foreach ($attendee['interests'] as $session) {
					if (in_array($session, $this->partySessions)) {
						throw new \Exception('Partyists are not allowed to be mapped to any other sessions! "person_id": ' . $attendee['id']);
					}
				}
			}
		}

		$this->addViewData(array(
			'sides' => array('left', 'right'),
			'event' => $event,
			'attendees' => $attendees,
		));
		$this->renderView('badges');
	}

	public function post() {

		$data = array_merge($this->req->getPost(), array('id'=>$this->person['id']));
		$this->app->db->query('UPDATE people SET {given_name}, {family_name}, {email}, {org}, {bio}, {travel_origin} WHERE {id}', $data);

		$this->alert('info', 'Person updated');
		$this->resp->redirect('/admin/people');
	}

}
