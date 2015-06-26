<?php

namespace Controllers\PublicSite;

class QueueController extends \Controllers\PublicSite\PublicBaseController {

	private $sseduration = 50;

	public function get() {

		// If a session is in progress, kill it to avoid blocking
		if (session_id()) {
			session_write_close();
		}

		if ($this->req->getHeader('Accept') == 'text/event-stream') {

			header("Content-Type: text/event-stream");

			// Send around 4KB of padding
			for ($i=1; $i<60; $i++) {
				echo ':'.str_repeat(".", 70)."\n";
			}
			echo "\n";

			echo "retry: 1000\n\n";

			if ($this->req->getHeader('Last-Event-ID')) {
				$start = $this->req->getHeader('Last-Event-ID');
			} else {
				$start = $this->app->db->querySingle('SELECT MAX(id) FROM queueevents');
			}

			for ($i=1; $i<=$this->sseduration; $i++) {
				$res = $this->app->db->query('SELECT * FROM queueevents WHERE id > %d', $start);
				foreach ($res as $row) {
					echo "id: ".$row['id']."\n";
					echo "event: ".$row['event']."\n";
					echo "data: ".$row['data']."\n\n";

					// Something seems to be buffering, so we'll live with long polling
					exit;
				}

				if (ob_get_level()) ob_flush();
				flush();
				sleep(1);
			}

			// Don't serve a response through the response class
			exit;
		} else {
			$this->addViewData('queue', $this->getQueue());
			$this->renderView('speaker-queue');
		}
	}

	public function post() {

		$user = $this->req->getPost('user_name');
		$body = $this->req->getPost('text');
		$channel = $this->req->getPost('channel_name');

		$session_channel = $this->app->db->querySingle('SELECT slack_channel FROM sessions WHERE start_time < NOW() AND end_time > NOW()');

		$resp = '';

		// Add/remove from speaker queue
		if (!$body) {

			$person = $this->app->db->queryRow('SELECT * FROM people WHERE slack_username=%s', $user);
			if (!$person) {
				$resp = "This slack account is not associated with an Edge delegate registration.  Only delegates in the room can speak on mic.  To associate, say `/q register <email>` with the email address you used to buy your Edge ticket.";
			} else {
				if ($person['queued_to_speak']) {
					$this->triggerEvent('remove', $person['id']);
					$resp = "Removed you from the speaking queue";
				} else {
					$this->triggerEvent('add', $person['id']);
					$resp = "Added you to the speaking queue.  To remove, say `/q` again.";
				}
				$this->sendQueueToAdminChannel();
			}

		} elseif (preg_match("/^\s*register\s+(.*\@.*)$/i", $body, $m)) {
			$email = $this->canonicaliseEmail($m[1]);
			$person = $this->app->db->queryRow('SELECT * FROM people WHERE email=%s', $email);
			if ($person) {
				$this->app->db->query('UPDATE people SET slack_username=%s WHERE id=%d', $user, $person['id']);
				$resp = "Associated your slack account with the delegate profile for *".$person['given_name']." ".$person['family_name']."*, ".$person['org'].".  To join the speaker queue now, just say `/q`.";
			} else {
				$resp = 'That email address is not on our guestlist.  Check for typos and make sure you used the same account you registered with.  If you want us to look it up for you, just contact an organiser';
			}

		} elseif ($body === 'clear') {
			if ($channel === $this->app->config->slack->queue_admin_channel) {
				$queue = $this->getQueue();
				foreach ($queue as $id => $person) {
					$this->triggerEvent('remove', $id);
				}
				$resp = "Queue cleared";
				$this->sendPublicMsg($this->app->config->slack->queue_admin_channel, 'Speaking queue cleared by @'.$user);
				if ($session_channel) {
					$this->sendPublicMsg($session_channel, 'The speaking queue has been cleared by a moderator.  Anyone already in the queue, type `/q` to queue yourself anew.');
				}
			} else {
				$resp = 'Only moderators can do that, sorry';
			}
		} elseif (preg_match("/^\s*(\d+|\@.*)$/i", $body, $m)) {
			if (is_numeric($m[1])) {
				$person = $this->app->db->queryRow('SELECT * FROM people WHERE id=%d', $m[1]);
			} else {
				$m[1] = trim($m[1], '@');
				$person = $this->app->db->queryRow('SELECT * FROM people WHERE slack_username=%s', $m[1]);
			}
			if ($person) {
				if ($session_channel) {
					$this->sendPublicMsg($session_channel, 'Speaker: *'.$person['given_name'].' '.$person['family_name']. '*, '.$person['org']);
					$resp = 'Announced '.$person['given_name'].' '.$person['family_name'].' as the active speaker';
				} else {
					$resp = 'Cannot announce '.$person['given_name'].' '.$person['family_name'].' in Slack as no session is in progress that has a slack channel';
				}
				if ($person['queued_to_speak']) {
					$this->triggerEvent('remove', $person['id']);
					$this->sendQueueToAdminChannel();
				}
			} else {
				$resp = $m[1].' does not match a known user';
			}
		} else {
			$resp = 'Unrecognised command.  To join the speaking queue just type `/q`.';
		}

		if ($resp) $this->resp->setContent($resp);
	}

	private function triggerEvent($type, $id) {
		if ($type == 'add') {
			$this->app->db->query('UPDATE people SET queued_to_speak=%d WHERE id=%d', ($type=='add' ? 1:0), $id);
		}
		$queue = $this->getQueue();
		if ($type == 'remove') {
			$this->app->db->query('UPDATE people SET queued_to_speak=%d WHERE id=%d', ($type=='add' ? 1:0), $id);
		}
		$data = json_encode($queue[$id]);
		$this->app->db->query('INSERT INTO queueevents SET event=%s, data=%s', $type, $data);
	}

	private function getQueue() {
		$queue = $this->app->db->queryAllRows('SELECT id, given_name, family_name, org, email FROM people WHERE queued_to_speak = 1');
		$indexedqueue = array();
		foreach ($queue as $speaker) {
			$speaker['gravatar_hash'] = md5(trim(strtolower($speaker['email'])));
			unset($speaker['email']);
			$indexedqueue[$speaker['id']] = $speaker;
		}
		return $indexedqueue;
	}

	private function sendQueueToAdminChannel() {
		$queue = $this->getQueue();
		$queueop = array();
		foreach ($queue as $id => $spk) {
			$queueop[] = '    `'.$id.'` *'.$spk['given_name'].' '.$spk['family_name']. '*, '.$spk['org'];
		}
		$msg = count($queueop) ? "Updated speaker queue:\n".join("\n", $queueop) : 'The speaker queue is now empty.';
		$this->sendPublicMsg($this->app->config->slack->queue_admin_channel, $msg);
	}

	private function sendPublicMsg($channel, $msg) {
		$http = new \HTTP\HTTPRequest($this->app->config->slack->incoming_webhook);
		$http->setMethod('POST');
		$http->setRequestBody(json_encode(array(
			'channel' => '#'.$channel,
			'text' => $msg
		)));
		try {
			$http->send();
		} catch(Exception $e) {}
	}

	private function canonicaliseEmail($str) {
		// Get the email address
		list($username, $domain) = explode('@', $str);
		$domain = trim(strtolower($domain));

		// Canonicalise GMail addresses: googlemail and gmail are the same,
		// dots in username are ignored, as is anything after a +
		if ($domain == 'googlemail.com') $domain = 'gmail.com';
		if ($domain == 'gmail.com') {
			$username = str_replace('.', '', $username);
			$username = preg_replace('/\+.*$/', '', $username);
		}
		$email = $username . '@' . $domain;

		// Resolve aliases
		if ($e = $this->app->db->querySingle('SELECT target FROM emailaliases WHERE source=%s', $email)) {
			return $e;
		} else {
			return $email;
		}
	}
}
