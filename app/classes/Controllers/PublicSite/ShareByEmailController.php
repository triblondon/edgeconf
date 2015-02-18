<?php

namespace Controllers\PublicSite;

class ShareByEmailController extends \Controllers\PublicSite\PublicBaseController {

    public function post() {

		$this->authenticate();
		if (!$this->person) {
			$this->resp->setStatus(400);
			return $this->resp->setContent('You must be logged in to invite friends');
		}

		$email = $this->req->getPost('email');
		if ($alias = $this->app->db->querySingle('SELECT target FROM emailaliases WHERE source=%s', $email)) {
			$email = $alias;
		}

		// If user is already registered, don't send (but sleep for approximate duration of Sendgrid latency)
		if ($this->app->db->querySingle('SELECT 1 FROM attendance a INNER JOIN people p ON a.person_id=p.id WHERE p.email=%s AND a.event_id=%d', $email, $this->event['id'])) {
			sleep(1);
			return;
		}

		$viewdata = array(
			'summary' => $this->person['given_name'].' '.$this->person['family_name'].' thinks you would like Edge',
			'email' => $this->req->getPost('email'),
			'event' => $this->event,
			'person' => $this->person
		);

		$this->sendEmail(
			$this->req->getPost('email'),
			'Invitation to Edge conf '.$this->event['location'],
			$this->app->view->render('emails/share.txt', $viewdata),
			$this->app->view->render('emails/share.html', $viewdata)
		);
    }
}
