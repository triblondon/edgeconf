<?php

namespace Controllers\Admin;

class InviteController extends \Controllers\Admin\AdminBaseController {

	public function get() {

		// Get the next event
		$event = $this->app->db->queryRow('SELECT * FROM events WHERE end_time > NOW() ORDER BY start_time ASC LIMIT 1');

		// Fetch people who could be invited
		$invitable = $this->app->db->queryAllRows('SELECT p.id, p.given_name, p.family_name, p.email, p.org, GROUP_CONCAT(s.name) as sessions, AVG(pa.rating) as avgrating, a.type FROM people p INNER JOIN attendance a ON p.id=a.person_id AND a.event_id=%d LEFT JOIN participation pa ON p.id=pa.person_id INNER JOIN sessions s ON pa.session_id=s.id AND s.event_id=%d WHERE a.ticket_type IS NULL AND a.invite_code IS NULL GROUP BY p.id ORDER BY avgrating DESC', $event['id'], $event['id'], $event['id']);

		// Fetch people who could be reminded
		$remindable = $this->app->db->queryAllRows('SELECT p.id, p.given_name, p.family_name, p.email, p.org, GROUP_CONCAT(s.name) as sessions, AVG(pa.rating) as avgrating FROM people p INNER JOIN attendance a ON p.id=a.person_id AND a.event_id=%d LEFT JOIN participation pa ON p.id=pa.person_id INNER JOIN sessions s ON pa.session_id=s.id WHERE a.invite_code IS NOT NULL AND a.ticket_type IS NULL AND a.invite_date_reminded IS NULL AND a.invite_date_sent < (NOW() - INTERVAL 7 DAY) GROUP BY p.id ORDER BY avgrating DESC', $event['id']);

		$this->addViewData(array(
			'event' => $event,
			'invitable' => $invitable,
			'remindable' => $remindable,
		));
		$this->renderView('admin/invite');
	}

	public function post() {
		$this->app->db->query('START TRANSACTION');

		// Get the next event
		$event = $this->app->db->queryRow('SELECT * FROM events WHERE end_time > NOW() ORDER BY start_time ASC LIMIT 1');

		foreach ($this->req->getPost('people') as $personid) {
			$person = $this->app->db->queryRow('SELECT * FROM people WHERE id=%d', $personid);
			$attendance = $this->app->db->queryRow('SELECT * FROM attendance WHERE person_id=%d AND event_id=%d', $personid, $event['id']);

			if ($this->req->getPost('action') === 'invite') {

				$code = substr(str_shuffle('QWERTYUIOPASDFGHJKLZXCVBNM1234567890'), 0, 20);

				$viewdata = array(
					'summary'=>"You're invited to Edge: claim your ticket now",
					'person'=>$person,
					'event'=>$event,
					'attendance'=>$attendance,
					'code'=>$code
				);
				$htmloutput = $this->app->view->render('emails/invite.html', $viewdata);
				$textoutput = $this->app->view->render('emails/invite.txt', $viewdata);

				$this->app->db->query("UPDATE attendance SET invite_date_sent=NOW(), invite_code=%s WHERE person_id=%d AND event_id=%d", $code, $personid, $event['id']);

				$this->sendEmail($person['email'], 'Invite to Edge conf', $textoutput, $htmloutput);
				$this->alert('info', 'Sent invite to '.$person['email']);

			} else if (isset($_POST['action']) and $_POST['action'] == 'remind') {
				$this->app->db->query("UPDATE attendance SET invite_date_reminded=NOW() WHERE person_id=%d AND event_id=%d", $personid, $event['id']);
				$viewdata = array(
					'person'=>$person,
					'event'=>$event,
					'code'=>$this->app->db->querySingle("SELECT invite_code FROM attendance WHERE person_id=%d AND event_id=%d", $personid, $event['id'])
				);
				$htmloutput = $this->app->view->render('emails/reminder.html', $viewdata);
				$textoutput = $this->app->view->render('emails/reminder.txt', $viewdata);
				$this->sendEmail($person['email'], 'Reminder: Edge conf invite', $textoutput, $htmloutput);
				$this->alert('info', 'Sent reminder to '.$person['email']);
			}
		}
		$this->app->db->query("COMMIT");
		$this->resp->redirect('/admin/invite');
	}
}
