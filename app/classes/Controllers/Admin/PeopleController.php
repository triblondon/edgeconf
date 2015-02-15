<?php

namespace Controllers\Admin;

class PeopleController extends \Controllers\Admin\AdminBaseController {

	public function get() {

		$event = $this->app->db->queryRow('SELECT * FROM events WHERE end_time > NOW() ORDER BY start_time ASC LIMIT 1');
		$people = $this->app->db->queryAllRows('SELECT p.id, p.given_name, p.family_name, p.email, p.org, a.ticket_type, a.invite_code, a.invite_date_expired, a.event_id as attendance, a.expenses_travel, a.expenses_accom FROM people p LEFT JOIN attendance a ON p.id=a.person_id AND a.event_id=%d ORDER BY family_name', $event['id']);

		$totals = array('travel'=>0, 'accom'=>0);
		foreach ($people as &$person) {
			if ($person['ticket_type']) {
				$person['status'] = 'Attending ('.$person['ticket_type'].')';
			} else if ($person['invite_date_expired']) {
				$person['status'] = 'Invite expired';
			} else if ($person['invite_code']) {
				$person['status'] = 'Invited ('.$person['invite_code'].')';
			} else if ($person['attendance']) {
				$person['status'] = 'Registered';
			}
			if ($person['expenses_accom']) $totals['accom'] += $person['expenses_accom'];
			if ($person['expenses_travel']) $totals['travel'] += $person['expenses_travel'];
		}

		$this->addViewData(array(
			'event' => $event,
			'people' => $people,
			'totals' => $totals,
		));
		$this->renderView('admin/people');
	}

	public function post() {

		$this->app->db->query('START TRANSACTION');

		// Get the next event
		$event = $this->app->db->queryRow('SELECT * FROM events WHERE end_time > NOW() ORDER BY start_time ASC LIMIT 1');

		if ($this->req->getPost('action') == 'Merge' and is_array($this->req->getPost('people'))) {

			// Get all the people in the set, preferring those with gmail addresses and more events
			$people = $this->app->db->queryAllRows('SELECT p.*, SUM(IF(a.event_id, 1, 0)) as cntevents FROM people p LEFT JOIN attendance a ON p.id=a.person_id WHERE p.id IN %d|list GROUP BY p.id ORDER BY (p.email LIKE %s) DESC, cntevents DESC', $this->req->getPost('people'), '%@gmail.com');

			// Keep the first, and augment with any info from the others that is not on the first
			$keeppeople = array_shift($people);
			$keeppeopleid = $keeppeople['id'];
			$fields = array('given_name', 'family_name', 'org', 'bio', 'travel_origin');
			$removepeopleids = array();
			foreach ($people as $person) {
				$removepeopleids[] = $person['id'];
				$this->app->db->query('INSERT IGNORE INTO emailaliases SET source=%s, target=%s', $person['email'], $keeppeople['email']);
				foreach ($fields as $key) {
					if (empty($keeppeople[$key]) and !empty($person[$key])) {
						$keeppeople[$key] = $person[$key];
					}
				}
			}
			$this->app->db->query('UPDATE people SET {given_name}, {family_name}, {org}, {bio}, {travel_origin} WHERE {id}', $keeppeople);

			// Reassign simple attendences and participations which don't conflict
			$this->app->db->query('UPDATE IGNORE attendance SET person_id=%d WHERE person_id IN %d|list', $keeppeopleid, $removepeopleids);
			$this->app->db->query('UPDATE IGNORE participation SET person_id=%d WHERE person_id IN %d|list', $keeppeopleid, $removepeopleids);

			// Deal with remaining attendences by copying anything relevant to the existing attendance
			$fields = array('invite_code', 'invite_date_expired', 'invite_date_sent', 'invite_date_reminded', 'expenses_travel', 'expenses_accom', 'ticket_type', 'ticket_date');
			$removeatt = $this->app->db->query('SELECT * FROM attendance WHERE person_id IN %d|list', $removepeopleids);
			foreach ($removeatt as $att) {
				foreach ($fields as $key) {
					if (!empty($att[$key])) {
						$this->app->db->query('UPDATE attendance SET '.$key.'=%s WHERE person_id=%d AND event_id=%d AND '.$key.' IS NULL', $att[$key], $keeppeopleid, $att['event_id']);
					}
				}
			}
			$this->app->db->query('DELETE FROM attendance WHERE person_id IN %d|list', $removepeopleids);

			// Delete any surplus participations (crude)
			$this->app->db->query('DELETE FROM participation WHERE person_id IN %d|list', $removepeopleids);

			// Delete the extra people
			$this->app->db->query('DELETE FROM people WHERE id IN %d|list', $removepeopleids);

			$this->app->db->query('COMMIT');

			$this->alert('info', 'Merged '.(count($people)+1).' people into person '.$keeppeople['id'].' ('.$keeppeople['email'].')');

		} else if ($this->req->getPost('action') == 'VIP registration' and is_array($this->req->getPost('people'))) {
			foreach ($this->req->getPost('people') as $personid) {
				$this->app->db->query('INSERT IGNORE INTO attendance SET person_id=%d, event_id=%d, type=%s, created_at=NOW()', $personid, $event['id'], 'VIP');
			}
			$this->alert('info', 'Created VIP registrations');
			$this->app->db->query('COMMIT');
		}

		$this->resp->redirect('/admin/people');
	}

}
