<?php

namespace Controllers\Admin;

class InviteController extends \Controllers\Admin\AdminBaseController {

	public function get() {

		// Get the next event
		$event = $this->app->db->queryRow('SELECT * FROM events WHERE end_time > NOW() ORDER BY start_time ASC LIMIT 1');

		// Fetch people who could be invited
		$people = $this->app->db->queryAllRows('SELECT p.id, p.given_name, p.family_name, p.email, p.org, AVG(pa.rating) as avgrating, a.type, a.invite_code, a.invite_date_sent, a.invite_date_reminded, a.ticket_type, a.ticket_id, IF(ticket_type IS NOT NULL, %s, IF(a.invite_code IS NOT NULL, %s, %s)) as status FROM people p INNER JOIN attendance a ON p.id=a.person_id AND a.event_id=%d LEFT JOIN participation pa ON p.id=pa.person_id GROUP BY p.id ORDER BY status, a.type=%s DESC, avgrating DESC', 'Attending', 'Invited', 'Registered', $event['id'], $event['id'], 'VIP');

		$this->addViewData(array(
			'event' => $event,
			'people' => $people
		));
		$this->renderView('admin/invite');
	}

	public function post() {
		$this->app->db->query('START TRANSACTION');

		// Get the next event
		$event = $this->app->db->queryRow('SELECT * FROM events WHERE end_time > NOW() ORDER BY start_time ASC LIMIT 1');

		$personid = $this->req->getPost('person_id');
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

		} else if (isset($_POST['action']) and $_POST['action'] == 'cancel') {

			\Stripe::setApiKey($this->app->config->stripe->secret_key);

			try {
				$ch = \Stripe_Charge::retrieve($attendance['ticket_id']);
				$re = $ch->refunds->create();

				$this->app->db->query('DELETE FROM attendance WHERE person_id=%d AND event_id=%d', $personid, $event['id']);

			} catch(\Stripe_CardError $e) {
				$this->resp->setStatus(500);
				$this->resp->setJSON($e->getMessage());
			}
		}
		$this->app->db->query("COMMIT");
	}
}
