<?php

namespace Controllers\PublicSite;

class RegisterController extends \Controllers\PublicSite\PublicBaseController {

	private $sessions;

	public function get() {

		$this->authenticate();

		$this->sessions = $this->app->db->queryLookupTable('SELECT id as k, name as v FROM sessions WHERE event_id=%d AND type=%s ORDER BY start_time', $this->event['id'], 'Session');

		if (!empty($this->person)) {
			$this->person['attendance'] = $this->app->db->queryRow('SELECT * FROM attendance WHERE person_id=%d AND event_id=%d', $this->person['id'], $this->event['id']);
			$this->person['proposals'] = $this->app->db->queryLookupTable('SELECT session_id as k, proposal as v FROM participation WHERE person_id=%d AND session_id IN %d|list', $this->person['id'], array_keys($this->sessions));
			$this->person['sessions'] = array_keys($this->person['proposals']);
		} else {

			// If there's a state query param but user is not logged in, strip query string and redirect
			if ($this->req->getQuery('state')) {
				return $this->resp->redirect($this->req->getPath());
			}
		}

		$countries = $this->app->db->queryLookupTable('SELECT iso as k, name as v FROM countries ORDER BY name');
		$countries['--'] = 'Rather not say';
		$this->addViewData(array(
			'person'=> $this->person,
			'sessions' => $this->sessions,
			'countries' => $countries,
			'stripe_key' => $this->app->config->stripe->public_key,
			'state' => $this->req->getQuery('state'),
			'closed' => !$this->event['ticketsavailable']
		));

		// Google auth redirect URL - currently not used for public site
		if (!$this->person) {
			$this->addViewData('auth_url', $this->app->auth->getAuthRedirectUrl());
		}
		$this->resp->setCacheTTL(0);
		$this->renderView('register');
	}

	public function post() {

		$this->authenticate();

		$this->sessions = $this->app->db->queryLookupTable('SELECT id as k, name as v FROM sessions WHERE event_id=%d AND type=%s ORDER BY start_time', $this->event['id'], 'Session');

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
		$this->resp->redirect($this->req->getPath().'?state=registered');
	}
}
