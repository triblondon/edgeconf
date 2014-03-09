<?php

namespace Controllers\Admin;

class PersonController extends \Controllers\Admin\AdminBaseController {

	private $person;

	public function initialise() {
		$result = parent::initialise();
		if ($result !== null) return $result;
		$this->person = $this->app->db->queryRow('SELECT * FROM people WHERE {id}', $this->routeargs);
		if (!$this->person) return false;
	}

	public function get() {

		$attendances = $this->app->db->queryAllRows('SELECT e.location, a.* FROM events e INNER JOIN attendance a ON e.id=a.event_id WHERE a.person_id=%d', $this->person['id']);
		foreach ($attendances as &$attendance) {
			if ($attendance['ticket_type']) {
				$attendance['status'] = 'Attending ('.$attendance['ticket_type'].')';
			} else if ($attendance['invite_date_expired']) {
				$attendance['status'] = 'Invite expired';
			} else if ($attendance['invite_code']) {
				$attendance['status'] = 'Invited';
			} else {
				$attendance['status'] = 'Registered';
			}
			$attendance['sessions'] = $this->app->db->queryAllRows('SELECT s.id, s.name, p.proposal, p.role, p.panel_status FROM sessions s LEFT JOIN participation p ON p.session_id=s.id AND p.person_id=%d WHERE s.event_id=%d AND s.type=%s', $this->person['id'], $attendance['event_id'], 'Session');
		}

		$this->addViewData(array(
			'person' => $this->person,
			'countries' => $this->app->db->queryLookupTable('SELECT iso as k, name as v FROM countries ORDER BY name'),
			'attendances' => $attendances,
			'panel_status_options' => array(null, 'Potential', 'Invited', 'Interested', 'Confirmed', 'Rejected', 'Declined'),
			'role_options' => array(null, 'Delegate', 'Panelist', 'Speaker', 'Moderator'),
		));
		$this->renderView('admin/person');
	}

	public function post() {

		$data = array_merge($this->req->getPost(), array('id'=>$this->person['id']));
		$this->app->db->query('UPDATE people SET {given_name}, {family_name}, {email}, {org}, {bio}, {travel_origin} WHERE {id}', $data);

		foreach ($this->req->getPost() as $key => $val) {
			if ($val==='') $val = null;
			if (preg_match('/^(role|panel_status)_(\d+)$/', $key, $m)) {
				$this->app->db->query('INSERT INTO participation SET person_id=%d, session_id=%d, '.$m[1].'=%s, created_at=NOW() ON DUPLICATE KEY UPDATE '.$m[1].'=%s', $this->person['id'], $m[2], $val, $val);
			} elseif (preg_match('/^(expenses_travel|expenses_accom|invite_code)_(\d+)$/', $key, $m)) {
				$this->app->db->query('INSERT INTO attendance SET person_id=%d, event_id=%d, '.$m[1].'=%s, created_at=NOW() ON DUPLICATE KEY UPDATE '.$m[1].'=%s', $this->person['id'], $m[2], $val, $val);
			}
		}

		$this->alert('info', 'Person updated');
		$this->resp->redirect('/admin/people');
	}

}
