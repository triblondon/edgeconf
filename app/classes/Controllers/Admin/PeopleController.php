<?php

namespace Controllers\Admin;

class PeopleController extends \Controllers\Admin\AdminBaseController {

	public function get() {

		$eberrors = $this->syncFromEventbrite();
		if ($eberrors) {
			foreach ($eberrors as $error) $this->alert('warning', $error);
		}

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

	private function syncFromEventbrite() {

		$this->app->db->query('START TRANSACTION');

		try {
			$events = $this->app->db->queryAllRows('SELECT id, location, eventbrite_id FROM events WHERE eventbrite_id IS NOT NULL AND end_time > NOW()');
			foreach ($events as $event) {

				$errors = array();
				$ticketsales = array();
				$updated = 0;

				// Fetch ticket types
				$tickets = array();
				$evt = $this->app->eb->event_get(array('id'=>$event['eventbrite_id']));
				foreach($evt->event->tickets as $tkttype) {
					$tickets[$tkttype->ticket->id] = $tkttype->ticket->name;
				}

				// Fetch all attendees of the event from EB
				$list = $this->app->eb->event_list_attendees(array('id'=>$event['eventbrite_id']));
				foreach ($list->attendees as $rec) {
					$data = array(
						'given_name' => ucwords(trim($rec->attendee->first_name)),
						'family_name' => ucwords(trim($rec->attendee->last_name)),
						'org' => isset($rec->attendee->company) ? trim($rec->attendee->company) : null,
						'email' => strtolower($rec->attendee->email),
						'ticket_date' => $rec->attendee->created,
						'ticket_type' => $tickets[$rec->attendee->ticket_id],
						'invite_code' => isset($rec->attendee->discount) ? $rec->attendee->discount : null,
						'event_id' => $event['id']
					);

					// Remove the invite code if it's the VIP one
					if ($data['invite_code'] == $this->app->config->eventbrite->vipcode or $data['invite_code'] == 'OBRAFH') {
						$data['invite_code'] = null;
					}

					// Canonicalise the email address from EB (to match GAuth)
					// googlemail and gmail are the same, dots in username are ignored, as is anything after a +
					list($username, $domain) = explode('@', $data['email'], 2);
					if ($domain == 'googlemail.com') $domain = 'gmail.com';
					if ($domain == 'gmail.com') {
						$username = str_replace('.', '', $username);
						$username = preg_replace('/\+.*$/', '', $username);
					}
					$data['email'] = $username . '@' . $domain;

					// Check for aliases
					$target = $this->app->db->querySingle('SELECT target FROM emailaliases WHERE source=%s', $data['email']);
					if ($target) $data['email'] = $target;

					$att = false;
					if ($data['invite_code']) {
						$att = $this->app->db->queryRow('SELECT a.*, p.email, p.given_name, p.family_name FROM attendance a INNER JOIN people p ON a.person_id=p.id WHERE {invite_code} AND {event_id}', $data);
					}
					if (!$att) {
						$att = $this->app->db->queryRow('SELECT a.*, p.email, p.given_name, p.family_name FROM attendance a INNER JOIN people p ON a.person_id=p.id WHERE {email} AND {event_id}', $data);
					}
					if (!$att) {
						$personset = $this->app->db->queryAllRows('SELECT * FROM people WHERE {email} or ({given_name} AND {family_name}) ORDER BY ({email}) DESC', $data);
						if (!$personset) {
							$res = $this->app->db->query('INSERT INTO people SET {email}, {given_name}, {family_name}, {org}, created_at=NOW()', $data);
							$data['person_id'] = $res->getInsertId();
							$this->app->db->query('INSERT INTO attendance SET {event_id}, {person_id}, {invite_code}, {ticket_type}, {ticket_date|date}, created_at=NOW()', $data);
						} else if (count($personset) and $personset[0]['email'] == $data['email']) {
							$data['person_id'] = $personset[0]['id'];
							$this->app->db->query('INSERT INTO attendance SET {event_id}, {person_id}, {invite_code}, {ticket_type}, {ticket_date|date}, created_at=NOW()', $data);
						} else if (count($personset) == 1) {
							$errors[] = 'No attendance record for eventbrite ticket issued to '.$data['email'].' with code '.$data['invite_code'].', no person with that email on file but name match for '.$data['given_name'].' '.$data['family_name'].': person '.$personset[0]['id'];
						} else {
							$errors[] = 'No attendance record for eventbrite ticket issued to '.$data['email'].' with code '.$data['invite_code'].', and multiple people have the name \''.$data['given_name'].' '.$data['family_name'].'\'';
						}

					} else if (($att['invite_code'] == $data['invite_code'] or !$data['invite_code']) and $att['email'] === $data['email']) {
						$data['person_id'] = $att['person_id'];
						$this->app->db->query('UPDATE attendance SET {ticket_type}, {ticket_date|date} WHERE {event_id} AND {person_id}', $data);
						if ($att['given_name'] !== $data['given_name'] or $att['family_name'] !== $data['family_name']) {
							$this->app->db->query('UPDATE people SET given_name=%s, family_name=%s WHERE id=%d', $data['given_name'], $data['family_name'], $data['person_id']);
						}
					} else {
						$errors[] = 'Eventbrite ticket '.$data['email'].' ('.$data['invite_code'].') is partial match for person '.$att['person_id'].' ('.$att['given_name'].' '.$att['family_name'].' '.$att['email'].').';
					}
					if (!empty($data['person_id'])) $ticketsales[] = $data['person_id'];
				}
			}
		} catch (Exception $e) {

			// If no tickets have been sold
			if ($e->getMessage() != 'No records were found with the given parameters..') throw $e;
		}

		// Check DB for sales that no longer seem to be in Eventbrite
		if ($ticketsales) {
			$orphans = $this->app->db->queryList('SELECT person_id FROM attendance WHERE ticket_type IS NOT NULL AND event_id=%d AND person_id NOT IN %s|list', $event['id'], $ticketsales);
			if ($orphans) {
				$errors[] = 'The following people are recorded as having been issued tickets on Eventbrite for Edge '.$event['id'].' but Eventbrite is no longer reporting their orders: '.join(', ', $orphans);
			}
		}

		if ($errors) {
			return $errors;
		} else {
			$this->app->db->query('COMMIT');
		}
	}
}
