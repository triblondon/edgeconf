<?php

namespace Controllers\PublicSite;

class PublicBaseController extends \Controllers\BaseController {

	protected $event, $user, $person;
	private $currencysymbs = array('GBP'=>'£', 'USD'=>'$', 'EUR'=>'€');

	public function initialise() {
		if (!empty($this->routeargs['eventslug'])) {
			$this->event = $this->app->db->queryRow('SELECT * FROM events WHERE slug=%s', $this->routeargs['eventslug']);

			// If the requested event doesn't exist, cancel the route to cause a 404
    		if (!$this->event) throw new \Routing\RouteRejectedException;

    		$this->event['ticketsavailable'] = (boolean)$this->app->db->querySingle('SELECT 1 FROM events e WHERE e.id=%d AND e.start_time > NOW() AND (SELECT COUNT(*) FROM attendance a WHERE a.event_id = e.id AND a.ticket_type IS NOT NULL) < e.capacity', $this->event['id']);

			$this->event['currency_symb'] = empty($currencysymbs[$this->event['currency']]) ? $this->event['currency'] : $currencysymbs[$this->event['currency']];

			$in7days = new \DateTime('+7 days');
			$this->event['cancelable'] = ($this->event['start_time'] > $in7days);
			$canceldate = clone $this->event['start_time'];
			$canceldate->sub(new \DateInterval('P7D'));
			$this->event['latest_cancel_date'] = $canceldate;

			$this->addViewData('thisevent', $this->event);
		}

		// Also add details of the next event (may be the same)
		$event = $this->app->db->queryRow('SELECT * FROM events WHERE end_time > NOW() ORDER BY start_time ASC LIMIT 1');
		if ($event) {
			$this->addViewData('nextevent', $event);
			$this->nextevent = $event;
		}
	}

	protected function authenticate() {

		$email = null;
		if (!session_id()) @session_start();

		if ($code = $this->req->getQuery('invite_preauth')) {
			$email = $this->app->db->querySingle('SELECT p.email FROM attendance a INNER JOIN people p ON a.person_id=p.id WHERE a.invite_code=%s AND a.event_id=%d', $code, $this->event['id']);

		} elseif ($code = $this->req->getPost('session_auth')) {
			$e = $this->req->getPost('email');
			if (!empty($_SESSION['emailverify'][$e]) and $_SESSION['emailverify'][$e] == trim($code)) {
				$email = $e;
			}
		}

		if ($email) {

			// Get the email address
			list($username, $domain) = explode('@', $email);

			// Canonicalise GMail addresses: googlemail and gmail are the same,
			// dots in username are ignored, as is anything after a +
			if ($domain == 'googlemail.com') $domain = 'gmail.com';
			if ($domain == 'gmail.com') {
				$username = str_replace('.', '', $username);
				$username = preg_replace('/\+.*$/', '', $username);
			}

			// Inject a user into the session var used by GoogleAuth, so it thinks the user is already authenticated
			$_SESSION['user'] = array('email' => $username . '@' . strtolower($domain));
		}

		// Allow admins to use Google auth on public pages
		$this->user = $this->app->auth->authenticate(false);
		if ($this->user) {
			$target = $this->app->db->querySingle('SELECT target FROM emailaliases WHERE source=%s', $this->user['email']);
			if ($target) $user['email'] = $target;
			$this->addViewData('user', $this->user);

			// Provide full person data if there's a  match
			$persondata = $this->app->db->queryRow('SELECT * FROM people WHERE email=%s', $this->user['email']);
			if ($persondata) {
				$this->person = $persondata;
				$this->addViewData('user', $this->person);
			}
		}
	}
}
