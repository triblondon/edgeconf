<?php

namespace Controllers\Admin;

class InviteController extends \Controllers\Admin\AdminBaseController {

	public function get() {

		// Get the next event
		$event = $this->app->db->queryRow('SELECT * FROM events WHERE end_time > NOW() ORDER BY start_time ASC LIMIT 1');

		// Fetch people who could be invited
		$invitable = $this->app->db->queryAllRows('SELECT p.id, p.given_name, p.family_name, p.email, p.org, GROUP_CONCAT(s.name) as sessions, AVG(pa.rating) as avgrating FROM people p INNER JOIN attendance a ON p.id=a.person_id AND a.event_id=%d LEFT JOIN participation pa ON p.id=pa.person_id LEFT JOIN sessions s ON pa.session_id=s.id WHERE a.ticket_type IS NULL AND a.invite_code IS NULL GROUP BY p.id ORDER BY avgrating DESC', $event['id']);

		// Fetch people who could be reminded
		$remindable = $this->app->db->queryAllRows('SELECT p.id, p.given_name, p.family_name, p.email, p.org, GROUP_CONCAT(s.name) as sessions, AVG(pa.rating) as avgrating FROM people p INNER JOIN attendance a ON p.id=a.person_id AND a.event_id=%d LEFT JOIN participation pa ON p.id=pa.person_id LEFT JOIN sessions s ON pa.session_id=s.id WHERE a.invite_code IS NOT NULL AND a.ticket_type IS NULL AND a.invite_date_reminded IS NULL AND a.invite_date_sent < (NOW() - INTERVAL 7 DAY) GROUP BY p.id ORDER BY avgrating DESC', $event['id']);

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

			if ($this->req->getPost('action') === 'invite') {
				$code = $this->app->db->querySingle('SELECT code FROM codes c LEFT JOIN attendance a ON c.code=a.invite_code WHERE a.invite_code IS NULL LIMIT 1;');
				if (!$code) {
					$this->alert('warning', 'Could not invite '.$person['email'].' because we\'re out of promo codes.');
					continue;
				}
				$this->app->db->query("UPDATE attendance SET invite_date_sent=NOW(), invite_code=%s WHERE person_id=%d AND event_id=%d", $code, $personid, $event['id']);

				// Change this to be based on activity once data from Wes is imported
				$avgrating = $this->app->db->querySingle('SELECT AVG(pa.rating) FROM participation pa INNER JOIN sessions s ON pa.session_id = s.id WHERE pa.person_id=%d AND s.event_id=%d', $personid, $event['id']);

				$viewdata = array(
					'person'=>$person,
					'event'=>$event,
					'code'=>$code
				);
				$htmloutput = $this->app->view->render('emails/invite.html', $viewdata);
				$textoutput = $this->app->view->render('emails/invite.txt', $viewdata);

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

	private function sendEmail($to, $subj, $text, $html) {

		// Set up mime headers...
		$mime1 = '==MultipartBoundary_'.md5(time() + rand());

		// Put together the message body
		$email = 'This is a multipart message in MIME format.'."\n\n";
		$email .= '--'.$mime1."\n";
		$email .= 'Content-Type: text/plain; charset="UTF-8"'."\n";
		$email .= 'Content-Transfer-Encoding: base64'."\n\n";
		$email .= chunk_split(base64_encode($text), 76, "\n");
		if (substr($text, -1) != "\n") $email .= "\n";
		$email .= '--'.$mime1."\n";
		$email .= 'Content-Type: text/html; charset="UTF-8"'."\n";
		$email .= 'Content-Transfer-Encoding: base64'."\n\n";
		$email .= chunk_split(base64_encode($html), 76, "\n");
		if (substr($html, -1) != "\n") $email .= "\n";
		$email .= '--'.$mime1."--\n";

		// Finally set the MIME headers for this message
		$mimeheaders = "MIME-Version: 1.0\nContent-Type: multipart/alternative; boundary=\"".$mime1."\"\nFrom: \"Edge\" <edgeconf@labs.ft.com>\nReply-To: hello@edgeconf.com";

		// Send
		return mail($to, $subj, $email, $mimeheaders, '-f noreply@labs.ft.com');
	}
}
