<?php

namespace Controllers\PublicSite;

class RegisterController extends \Controllers\PublicSite\PublicBaseController {

	private $user, $sessions;

	public function get() {

		if ($this->loadCommon() === false) return false;

		if (!empty($this->user)) {
			$data = $this->app->db->queryRow('SELECT * FROM people WHERE email=%s', $this->user['email']);
			if (is_array($data)) {
				$existing = $this->app->db->queryRow('SELECT * FROM attendance WHERE person_id=%d AND event_id=%d', $data['id'], $this->event['id']);
				$this->user = $existing ? array_merge($data, $existing) : $data;
				$this->user['proposals'] = $this->app->db->queryLookupTable('SELECT session_id as k, proposal as v FROM participation WHERE person_id=%d AND session_id IN %d|list', $this->user['id'], array_keys($this->sessions));
				$this->user['sessions'] = array_keys($this->user['proposals']);
			}
		}

		$this->buildResponse();
	}

	public function post() {

		if ($this->loadCommon() === false) return false;

		$data = array_merge($this->req->getPost(), array('email'=>$this->user['email'], 'event_id'=>$this->event['id']));

		// Insert a new person record or update the details if already there
		$this->app->db->query('INSERT INTO people SET {email}, {given_name}, {family_name}, {travel_origin}, {org}, created_at=NOW() ON DUPLICATE KEY UPDATE {given_name}, {family_name}, {travel_origin}, {org}', $data);
		$data['person_id'] = $this->app->db->querySingle('SELECT id FROM people WHERE {email}', $data);

		// Nothing on attendance record can be modified, so just ignore it if it's already there
		$this->app->db->query('INSERT IGNORE INTO attendance SET {person_id}, {event_id}, created_at=NOW()', $data);

		// Save session participation proposals
		foreach ($data['sessions'] as $session) {
			$proposaldata = array_merge($data, array(
				"session_id" => $session,
				"proposal" => $data['proposal_'.$session],
				"role" => "Delegate"
			));
			$this->app->db->query('INSERT INTO participation SET {person_id}, {session_id}, {proposal}, {role}, created_at=NOW() ON DUPLICATE KEY UPDATE {proposal}, rating=NULL, rated_by=NULL, rating_date=NULL', $proposaldata);
		}
		$this->addViewData('saved', true);

		$this->buildResponse();
	}


	/* Methods shared by GET and POST requests */

	private function loadCommon() {

		// Authenticate the user
		$this->user = $this->app->auth->authenticate(false);

		// Check for email aliases
		if ($this->user) {
			$target = $this->app->db->querySingle('SELECT target FROM emailaliases WHERE source=%s', $this->user['email']);
			if ($target) $this->user['email'] = $target;
		}

		$this->sessions = $this->app->db->queryLookupTable('SELECT id as k, name as v FROM sessions WHERE event_id=%d AND type=%s ORDER BY start_time', $this->event['id'], 'Session');
	}

	private function buildResponse() {
		$countries = $this->app->db->queryLookupTable('SELECT iso as k, name as v FROM countries ORDER BY name');
		$countries['--'] = 'Rather not say';
		$this->addViewData(array(
			'user' => $this->user,
			'sessions' => $this->sessions,
			'countries' => $countries,
		));
		if (!$this->user) {
			$this->addViewData('auth_url', $this->app->auth->getAuthRedirectUrl());
		}
		$this->resp->setCacheTTL(0);
		$this->renderView('register');
	}

}
