<style>
body { font-family: sans-serif; margin: 15px; }
table { border: 1px solid #aaa; border-collapse: collapse; margin: 1em 0;}
th, td { border: 1px solid #aaa; padding: 3px 5px; text-align: left; font-size:12px; }
.error { color: red; }
</style>
<h1>Send invitations</h1>
<?php

require_once '../../app/global';

$user = adminOnly();
$db = new MySQLConnection('localhost', MYSQL_USER, MYSQL_PASS, MYSQL_DBNAME);
$eb_client = new Eventbrite(array('app_key'=>EVENTBRITE_APPKEY, 'user_key'=>EVENTBRITE_USERKEY));

$sessions = $db->queryLookupTable('SELECT id as k, name as v FROM sessions WHERE eventid=%d ORDER BY starttime', EVENT_ID);
$preselect = array();

/* Download Eventbrite data and update local DB.  Output errors for mis-syncs */

$errors = array();
$ticketsales = array();
$updated = 0;
try {
	$tickets = array();
	$evt = $eb_client->event_get(array('id'=>EVENTBRITE_EID));
	foreach($evt->event->tickets as $tkttype) {
		$tickets[$tkttype->ticket->id] = $tkttype->ticket->name;
	}

	$list = $eb_client->event_list_attendees(array('id'=>EVENTBRITE_EID));
	foreach ($list->attendees as $rec) {
		$data = array(
			'givenname' => ucwords(trim($rec->attendee->first_name)),
			'familyname' => ucwords(trim($rec->attendee->last_name)),
			'org' => isset($rec->attendee->company) ? trim($rec->attendee->company) : null,
			'email' => strtolower($rec->attendee->email),
			'ticketdate' => $rec->attendee->created,
			'tickettype' => $tickets[$rec->attendee->ticket_id],
			'invitecode' => isset($rec->attendee->discount) ? $rec->attendee->discount : null,
			'eventid' => EVENT_ID
		);
		$attendanceset = $db->query('SELECT * FROM attendance a WHERE ({invitecode} OR {email}) AND {eventid}', $data);
		foreach ($attendanceset as $attendance) break;
		$person = $db->queryRow('SELECT * FROM people WHERE {email}', $data);
		if (!$person) {
			$errors[] = 'An Eventbrite ticket has been issued to email address '.$data['email'].', but they are not in the Edge DB.  Please add an entry to the people table for this person.';
		} else if (count($attendanceset) > 1) {
			$errors[] = 'One Eventbrite order has used invite code '.$data['invitecode'].' and email address '.$data['email'].', but these belong to two different attendees in the Edge DB.  It\'s possible that an invitee has sent their invite to someone else who had also applied.  We should discourage that.';
		} else if (count($attendanceset) == 0 and !empty($data['invitecode'])) {
			$errors[] = $data['email'].' has bought a ticket on Eventbrite with invite code '.$data['invitecode'].' but is not in the Edge DB, under either the email address or the invite code.  Maybe a row has been inadvertently deleted in the Edge DB, or maybe someone gave them a code without going through the invite script.  This ought not to happen.';
		} else if (count($attendanceset) == 0 and empty($data['invitecode'])) {
			$errors[] = $data['email'].' has bought a ticket on Eventbrite but is not in the Edge DB.  You probably invited a VIP directly via Eventbrite.  Please put them in the Edge DB too.';
		} else if ($attendance['email'] != $data['email']) {
			$errors[] = $data['email'].' has bought a ticket on Eventbrite but using an invite code assigned to '.$attendance['email'].'. We should either cancel the ticket and let the buyer know that it has to be bought by the invitee, transfer it to the invitee by updating the email address in Eventbrite (if they agree, this is preferred), or make an exception and update the Edge DB to transfer the invite to the person who actually bought it.';
		} else if (strtolower($person['givenname']) != strtolower($data['givenname'])) {
			$errors[] = 'Given name mismatch for '.$data['email'].': '.$data['givenname'].' on Eventbrite, '.$person['givenname'].' on Edge DB. Please correct the one that\'s wrong';
		} else if (strtolower($person['familyname']) != strtolower($data['familyname'])) {
			$errors[] = 'Family name mismatch for '.$data['email'].': '.$data['familyname'].' on Eventbrite, '.$person['familyname'].' on Edge DB. Please correct the one that\'s wrong';
		} else if (!empty($data['org']) and (strtolower($person['org']) != strtolower($data['org']))) {
			$errors[] = 'Organisation mismatch for '.$data['email'].': '.$data['org'].' on Eventbrite, '.$person['org'].' on Edge DB. Please delete the org on Eventbrite, as we only need it in Edge DB';
		} else {
			$res = $db->query('UPDATE attendance SET {tickettype}, {ticketdate} WHERE {email}', $data);
			if ($res->getAffectedRows()) $updated++;
			$ticketsales[] = $data['email'];
 		}
	}
} catch (Exception $e) {

	// If no tickets have been sold
	if ($e->getMessage() != 'No records were found with the given parameters..') throw $e;
}

// Check DB for sales that no longer seem to be in Eventbrite
if ($ticketsales) {
	$orphans = $db->queryList('SELECT email FROM attendance WHERE tickettype IS NOT NULL AND eventid=%d AND email NOT IN %s|list', EVENT_ID, $ticketsales);
	if ($orphans) {
		$errors[] = 'The following are recorded as having been issued tickets on Eventbrite but Eventbrite is no longer reporting their orders: '.join(', ', $orphans);
	}
}

if ($errors) {
	echo '<h2>Errors syncing from Eventbrite:</h2><ul><li>'.join('</li><li>', $errors).'</li></ul>';
} else if ($updated) {
	echo '<h2>Eventbrite sync</h2>';
	echo '<ul><li>'.$updated.' record(s) updated</li></ul>';
}


/* Process requests */

// Don't allow update request if Eventbrite has made updates.  We may no longer want to do some of the things we requested.
if (!empty($_POST) and $updated) {
	echo '<h2>Update rejected</h2><p>New data available from Eventbrite.  Please recheck your request to make sure it\'s still correct and submit again.</p>';
	$preselect = $_POST['invitees'];

} else if (isset($_POST['action']) and $_POST['action'] == 'invite') {
	$emailbody_html = file_get_contents('../../lib/templates/email/email-invite.html');
	$emailbody_text = file_get_contents('../../lib/templates/email/email-invite.txt');
	$results = array();
	foreach ($_POST['invitees'] as $recip) {
		$code = $db->querySingle('SELECT code FROM codes c LEFT JOIN attendance a ON c.code=a.invitecode WHERE a.invitecode IS NULL LIMIT 1;');
		if (!$code) {
			$results[] = 'Could not invite '.$recip.' because we\'re out of promo codes.';
			continue;
		}
		$db->query("UPDATE attendance SET invitedatesent=NOW(), invitecode=%s WHERE email=%s AND eventid=%d", $code, $recip, EVENT_ID);

		// Change this to be based on activity once data from Wes is imported
		$avgrating = $db->querySingle('SELECT AVG(rating) FROM participation WHERE email=%s AND sessionid IN %d|list', $recip, array_keys($sessions));
		$invitestr = (!$avgrating) ? "You are receiving this special invite because we think you'd bring enormous value to Edge, and we'd very much like to see you there.  This may be because you've contributed to a previous Edge event, or simply because we'd especially value your contribution.<br><br>You're invited to skip our normal ticket application process and book a ticket immediately." : "Thanks for registering, we're looking forward to seeing you on March 21st.";

		$placeholders = array('{email}', '{code}', '{invitestr}');
		$replacements = array($recip, $code, $invitestr);
		$htmloutput = str_replace($placeholders, $replacements, $emailbody_html);
		$textoutput = str_replace($placeholders, $replacements, $emailbody_text);

		sendEmail($person['email'], 'Invite to Edge conf', $textoutput, $htmloutput);
		$results[] = 'Sent invite to '.$recip;
	}
	echo "<h2>Result of sending invitations:</h2><ul><li>".join('</li><li>', $results).'</li></ul>';

} else if (isset($_POST['action']) and $_POST['action'] == 'remind') {
	$reminderbody_html = file_get_contents('../../lib/templates/email/email-reminder.html');
	$reminderbody_text = file_get_contents('../../lib/templates/email/email-reminder.txt');
	$results = array();
	foreach ($_POST['invitees'] as $recip) {
		$db->query("UPDATE attendance SET invitedatereminded=NOW() WHERE email=%s AND eventid=%d", $recip, EVENT_ID);
		$code = $db->querySingle("SELECT invitecode FROM attendance WHERE email=%s AND eventid=%d", $recip, EVENT_ID);
		$placeholders = array('{email}', '{code}');
		$replacements = array($recip, $code);
		$htmloutput = str_replace($placeholders, $replacements, $reminderbody_html);
		$textoutput = str_replace($placeholders, $replacements, $reminderbody_text);
		sendEmail($recip, 'Reminder: Edge conf invite', $textoutput, $htmloutput);
		$results[] = 'Sent reminder to '.$recip;
	}
	echo "<h2>Result of sending reminders:</h2><ul><li>".join('</li><li>', $results).'</li></ul>';
}




/* Show current attendence stats and possible invitees */

?>
<h2>Stats by interest area</h2>
<p>The following numbers are subsets from left to right - interested is everyone who has declared an interest in this session, including those who have been invited.  Invited is a subset of that.  Confirmed is a subset of invited.</p>
<table>
<thead>
<tr>
	<th>Session</th><th>Interested</th><th>Invited</th><th>Invited last 2 weeks</th><th>Confirmed</th>
</tr>
</thead>
<tbody>
	<?php
	foreach ($sessions as $id=>$name) {
		$stats = $db->queryRow('SELECT COUNT(*) as interested, SUM(IF(invitecode IS NOT NULL,1,0)) as invited, SUM(IF(invitedatesent > (NOW() - INTERVAL 2 WEEK),1,0)) as recent, SUM(IF(tickettype IS NOT NULL,1,0)) as confirmed FROM attendance a INNER JOIN participation p ON a.email=p.email WHERE a.eventid=%d AND p.sessionid=%d', EVENT_ID, $id);
		echo "<tr>";
		echo "<td>".$name."</td>";
		echo "<td>".$stats['interested']."</td>";
		echo "<td>".$stats['invited']."</td>";
		echo "<td>".$stats['recent']."</td>";
		echo "<td>".$stats['confirmed']."</td>";
		echo "</tr>";
	}
	$stats = $db->queryRow('SELECT COUNT(*) as interested, SUM(IF(invitecode IS NOT NULL,1,0)) as invited, SUM(IF(invitedatesent > (NOW() - INTERVAL 2 WEEK),1,0)) as recent, SUM(IF(tickettype IS NOT NULL,1,0)) as confirmed FROM attendance a LEFT JOIN participation p ON a.email=p.email AND p.sessionid NOT IN %d|list WHERE a.eventid=%d AND p.email IS NULL', array_keys($sessions), EVENT_ID);
	echo "<tr>";
	echo "<td><em>No session participation</em></td>";
	echo "<td>".$stats['interested']."</td>";
	echo "<td>".$stats['invited']."</td>";
	echo "<td>".$stats['recent']."</td>";
	echo "<td>".$stats['confirmed']."</td>";
	echo "</tr>";
	?>
</tbody>
</table>

<h2>Send new invitations</h2>
<p>These applicants have an attendence for the current event, but do not yet have a ticket or an invite</p>
<form method='post' action='<?php echo $_SERVER['REQUEST_URI']; ?>'>
<table>
<thead>
<tr>
	<th>&nbsp;</th>
	<th>Email</th>
	<th>Name</th>
	<th>Org</th>
	<th>Avg rating</th>
	<th>Ratings</th>
</tr>
<tbody>
<?php

$prospects = $db->query('SELECT p.email, p.givenname, p.familyname, p.org, AVG(pa.rating) as avgrating FROM attendance a INNER JOIN people p ON a.email=p.email LEFT JOIN participation pa ON a.email=pa.email AND pa.sessionid IN %d|list WHERE a.eventid=%d AND a.tickettype IS NULL AND a.invitecode IS NULL GROUP BY p.email ORDER BY avgrating DESC', array_keys($sessions), EVENT_ID);
foreach ($prospects as $prospect) {
	$proposals = $db->query('SELECT sessionid, rating FROM participation WHERE email=%s AND sessionid IN %d|list', $prospect['email'], array_keys($sessions));
	$ratings = array();
	foreach ($proposals as $p) $ratings[] = $sessions[$p['sessionid']].': '.$p['rating'];

	echo "<tr>";
	echo "<td><input type='checkbox' name='invitees[]' value='".e($prospect,'email')."'".((in_array($prospect['email'], $preselect))?' checked':'')."></td>";
	echo "<td>".e($prospect, 'email')."</td>";
	echo "<td>".e($prospect, 'givenname')." ".e($prospect, 'familyname')."</td>";
	echo "<td>".e($prospect, 'org')."</td>";
	echo "<td>".round($prospect['avgrating'],2)."</td>";
	echo "<td>".join(', ', $ratings)."</td>";
	echo "</tr>";
}

?>
</tbody>
</table>
<input type='hidden' name='action' value='invite' />
<input type='submit' value='Invite selected' />
</form>

<h2>Send reminders</h2>
<p>These applicants have an invite for the current event, but haven't used it yet, and have not yet received a reminder.  They're listed in order of oldest invite first. Don't remind people who've only received an invite!</p>
<form method='post' action='<?php echo $_SERVER['REQUEST_URI']; ?>'>
<table>
<thead>
<tr>
	<th>&nbsp;</th>
	<th>Email</th>
	<th>Name</th>
	<th>Org</th>
	<th>Avg rating</th>
	<th>Age of invite</th>
</tr>
<tbody>
<?php

$prospects = $db->query('SELECT p.email, p.givenname, p.familyname, p.org, AVG(pa.rating) as avgrating, a.invitedatesent FROM attendance a INNER JOIN people p ON a.email=p.email LEFT JOIN participation pa ON a.email=pa.email AND pa.sessionid IN %d|list WHERE a.eventid=%d AND a.tickettype IS NULL AND a.invitecode IS NOT NULL AND a.invitedatereminded IS NULL GROUP BY p.email ORDER BY a.invitedatesent', array_keys($sessions), EVENT_ID);
foreach ($prospects as $prospect) {
	$datesent = new DateTime($prospect['invitedatesent'], new DateTimeZone('UTC'));
	$now = new DateTime();
	$age = $now->diff($datesent)->format('%a');
	echo "<tr>";
	echo "<td><input type='checkbox' name='invitees[]' value='".e($prospect,'email')."'".((in_array($prospect['email'], $preselect))?' checked':'')."></td>";
	echo "<td>".e($prospect, 'email')."</td>";
	echo "<td>".e($prospect, 'givenname')." ".e($prospect, 'familyname')."</td>";
	echo "<td>".e($prospect, 'org')."</td>";
	echo "<td>".round($prospect['avgrating'],2)."</td>";
	echo "<td>".$age." day(s)</td>";
	echo "</tr>";
}

?>
</tbody>
</table>
<input type='hidden' name='action' value='remind' />
<input type='submit' value='Remind selected' />
</form>
<?php


















/* Expire invitations more than 7 days old (for anyone from a non-whitelisted org). */
/*
// Create lookup table of discount code to Eventbrite discount ID
echo "<p>Expiring old codes: ";
flush();
$list = $eb_client->event_list_access_codes(array('id'=>EVENTBRITE_EID));
$promocodes = array();
foreach ($list->access_codes as $accesscode) {
	$promocodes[$accesscode->access_code->code] = $accesscode->access_code->access_code_id;
}
$oldinvites = $db->query('SELECT * FROM invites WHERE dateinvited < (NOW() - INTERVAL 7 DAY) AND ebt_datepurchased IS NULL AND dateexpired IS NULL');
$count = 0;
foreach ($oldinvites as $invite) {
	if (!preg_match('/(google|facebook|twitter|github|hubspot|microsoft)/i', $invite['org'])) {
		$eb_client->access_code_update(array('id'=>$promocodes[$invite['code']], 'end_date'=>date('Y-m-d H:i:s', time()+10)));
		$people[$invite['email']]['dateexpired'] = time();
		$count++;
	}
}
echo $count. " invitations expired.</p>\n";
flush();
*/





function sendEmail($to, $subj, $text, $html) {
	static $count;

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
	$mimeheaders = "MIME-Version: 1.0\nContent-Type: multipart/alternative; boundary=\"".$mime1."\"\nFrom: \"Edge\" <edgeconf@labs.ft.com>\nReply-To: edgeconf@labs.ft.com";

	// Send
	$count++;
	//$to = 'andrew@labs.ft.com';
	return mail($to, $subj, $email, $mimeheaders, '-f noreply@labs.ft.com');
}

