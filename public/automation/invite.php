<style>
body { font-family: sans-serif; margin: 15px; }
table { border: 1px solid #aaa; border-collapse: collapse; margin: 1em 0;}
th, td { border: 1px solid #aaa; padding: 3px 5px; text-align: left; font-size:12px; }
.error { color: red; }
</style>
<?php


require_once '../../app/global';



/* Configurable stuff */

// How many invites to have out at once (should gradually be ramped up to be a bit more than the total capacity of the venue)
$eventcapacity = (empty($_GET['capacity']) or !is_numeric($_GET['capacity'])) ? 30 : $_GET['capacity'];

// Minimum delegate rating in order to get an invite
$minimumrating = (empty($_GET['minrating']) or !is_numeric($_GET['minrating'])) ? 5 : $_GET['minrating'];

// Where to send invites, if not to the delegate themselves (for testing)
$overrideemail = (empty($_GET['overrideemail'])) ? null : $_GET['overrideemail'];

// How long after sending invite should reminder be sent if invite wasn't used? (days)
$reminderperiod = (empty($_GET['reminderperiod']) or !is_numeric($_GET['reminderperiod'])) ? 999 : $_GET['reminderperiod'];

// Whether to update mailchimp with participation status (defaults to false to avoid long pause)
$domailchimp = (empty($_GET['domailchimp'])) ? false : true;



$db = new MySQLConnection('localhost', MYSQL_USER, MYSQL_PASS, 'edge2');
$db->setQueryLogging(true);
$eb_client = new Eventbrite(array('app_key'=>EVENTBRITE_APPKEY, 'user_key'=>EVENTBRITE_USERKEY));

$emailbody_html = file_get_contents('../../lib/templates/email-invite.html');
$emailbody_text = file_get_contents('../../lib/templates/email-invite.txt');
$reminderbody_html = file_get_contents('../../lib/templates/email-reminder.html');
$reminderbody_text = file_get_contents('../../lib/templates/email-reminder.txt');
$sevendays = 60*60*24*7;
$tzutc = new DateTimeZone('UTC');

flush();
while(ob_get_level()) ob_end_flush();
flush();
echo '<!--'.str_repeat('#', 1024).'-->';


/* Get existing data from DB */

$people = array();
$res = $db->query('SELECT * FROM invites');
foreach ($res as $row) $people[$row['email']] = $row;


/* Download Google Docs data */

echo "<p>Downloading data from Google Drive</p>";
flush();
$http = new HTTPRequest(GSHEET_REG);
$resp = $http->send();

echo "<p>Decoding CSV</p>";
flush();
$data = $resp->getBody();
file_put_contents('/tmp/edgecsv', $data);
$csv = new Coseva\CSV('/tmp/edgecsv');
$csv->parse();
foreach ($csv as $row) {
	if ($row[0] == 'Ref' or empty($row[1])) continue;
	$data = array(
		'gdocs_ref' => $row[0],
		'gdocs_email' => strtolower(trim($row[1])),
		'gdocs_name' => ucwords(trim($row[3]) . ' ' . trim($row[2])),
		'gdocs_org' => trim($row[4]),
		'gdocs_rating' => $row[7],
		'participation' => 'Registrant'
	);
	$key = null;
	foreach ($people as $email => $person) {
		if (!empty($person['gdocs_ref']) and $person['gdocs_ref'] == $data['gdocs_ref']) {
			$key = $email;
			break;
		}
	}
	if (!$key) $key = $data['gdocs_email'];
	if (empty($people[$key])) $people[$key] = array();
	$people[$key] = array_merge($people[$key], $data);
}


/* Download Eventbrite data */

echo "<p>Downloading data from Eventbrite</p>";
flush();
$list = $eb_client->event_list_attendees(array('id'=>EVENTBRITE_EID));
foreach ($list->attendees as $rec) {
	$data = array(
		'ebt_name' => ucwords(trim($rec->attendee->first_name).' '.trim($rec->attendee->last_name)),
		'ebt_org' => isset($rec->attendee->company) ? trim($rec->attendee->company) : null,
		'ebt_email' => strtolower($rec->attendee->email),
		'ebt_datepurchased' => $rec->attendee->created,
		'code' => isset($rec->attendee->discount) ? $rec->attendee->discount : null,
		'participation' => 'Delegate'
	);
	if (!empty($data['code'])) {
		$key = null;
		foreach ($people as $email => $person) {
			if (!empty($person['code']) and $person['code'] == $data['code']) {
				$key = $email;
				break;
			}
		}
	} else {
		$key = $data['ebt_email'];
	}
	if (!isset($people[$key])) $people[$key] = array();
	$people[$key] = array_merge($people[$key], $data);
}


/* Add participation data */

echo "<p>Downloading VIP participation data from Google Drive</p>";
flush();
$http = new HTTPRequest(GSHEET_VIP);
$resp = $http->send();

echo "<p>Decoding CSV</p>";
flush();
$data = $resp->getBody();
file_put_contents('/tmp/edgecsv', $data);
$csv = new Coseva\CSV('/tmp/edgecsv');
$csv->parse();
foreach ($csv as $row) {
	if ($row[4] == 'Email' or empty($row[8])) continue;
	$data = array('participation' => $row[8]);
	$key = strtolower(trim($row[4]));
	if (empty($people[$key])) $people[$key] = array();
	$people[$key] = array_merge($people[$key], $data);
}


/* Canonicalise */

echo "<p>Canonicalising</p>";
$errors = array();
foreach ($people as $email => &$person) {
	if (isset($person['gdocs_email']) and isset($person['ebt_email'])) {
		if ($person['gdocs_email'] == $person['ebt_email']) {
			$person['email'] = $person['gdocs_email'];
			unset($person['gdocs_email'], $person['ebt_email']);
		} else {
			$errors[] = 'Email mismatch for '.$person['gdocs_email'].' (gdoc) vs '. $person['ebt_email']. ' (ebt)';
		}
	} elseif (isset($person['gdocs_email'])) {
		$person['email'] = $person['gdocs_email'];
		unset($person['gdocs_email']);
	} elseif (isset($person['ebt_email'])) {
		$person['email'] = $person['ebt_email'];
		unset($person['ebt_email']);
	} else {
		$errors[] = 'Email address '.$email.' is not in Google doc or eventbrite (probably a VIP you need to issue a comp ticket to)';
	}
	if (isset($person['gdocs_org']) and isset($person['ebt_org'])) {
		if ($person['gdocs_org'] == $person['ebt_org']) {
			$person['org'] = $person['gdocs_org'];
			unset($person['gdocs_org'], $person['ebt_org']);
		} else {
			$errors[] = 'Org mismatch for '.$email.': '.$person['gdocs_org'].' (gdoc) vs '. $person['ebt_org']. ' (ebt)';
		}
	} elseif (isset($person['gdocs_org'])) {
		$person['org'] = $person['gdocs_org'];
		unset($person['gdocs_org']);
	} elseif (isset($person['ebt_org'])) {
		$person['org'] = $person['ebt_org'];
		unset($person['ebt_org']);
	}
	if (isset($person['gdocs_name']) and isset($person['ebt_name'])) {
		if ($person['gdocs_name'] == $person['ebt_name']) {
			$person['name'] = $person['gdocs_name'];
			unset($person['gdocs_name'], $person['ebt_name']);
		} else {
			$errors[] = 'Name mismatch for '.$email.': '.$person['gdocs_name'].' (gdoc) vs '. $person['ebt_name']. ' (ebt)';
		}
	} elseif (isset($person['gdocs_name'])) {
		$person['name'] = $person['gdocs_name'];
		unset($person['gdocs_name']);
	} elseif (isset($person['ebt_name'])) {
		$person['name'] = $person['ebt_name'];
		unset($person['ebt_name']);
	}
	if (isset($person['email'])) {
		$sql = 'SELECT 1 FROM invites WHERE {email}';
		if (!empty($person['gdocs_ref'])) $sql .= ' OR {gdocs_ref}';
		if (!empty($person['code'])) $sql .= ' OR {code}';
		$res = $db->query($sql, $person);
		if (count($res) > 1) {
			$errors[] = 'Ambiguous email / gdocs ref / invite code usage: '.$res->getQueryExpr();
		}
	}
}
if ($errors) {
	foreach ($errors as $err) {
		echo '<p class="error">'.$err.'</p>';
	}
	exit;
}


/* Update Mailchimp */

if ($domailchimp) {
	echo "<p>Updating Mailchimp</p>";
	$api = new MCAPI(MC_KEY);
	foreach ($people as $person) {
		if (!empty($person['participation'])) {
			$merge_vars = array(
				'GROUPINGS'=> array(
					array('name'=>'Edgeconf 2 participation', 'groups'=>$person['participation']),
				)
			);
			$retval = $api->listSubscribe(MC_LIST, $person['email'], $merge_vars, 'html', false, true, true, false);
		}
	}
}


/* Expire invitations more than 14 days old (for people < 5*). */

// Create lookup table of discount code to Eventbrite discount ID
echo "<p>Expiring old codes: ";
flush();
$list = $eb_client->event_list_access_codes(array('id'=>EVENTBRITE_EID));
$promocodes = array();
foreach ($list->access_codes as $accesscode) {
	$promocodes[$accesscode->access_code->code] = $accesscode->access_code->access_code_id;
}
$oldinvites = $db->query('SELECT * FROM invites WHERE dateinvited < (NOW() - INTERVAL 14 DAY) AND ebt_datepurchased IS NULL AND dateexpired IS NULL AND gdocs_rating < 5');
$count = 0;
foreach ($oldinvites as $invite) {
	$eb_client->access_code_update(array('id'=>$promocodes[$invite['code']], 'end_date'=>date('Y-m-d H:i:s', time()+10)));
	$people[$invite['email']]['dateexpired'] = time();
	$count++;
}
echo $count. " invitations expired.</p>\n";
flush();


/* Send invites and reminders as appropriate */

$stats = array('attending'=>0, 'invited'=>0, 'waitlist'=>0, 'expired'=>0);
echo "<table><tr><th>Email</th><th>Name</th><th>Org</th><th>Gdoc ref</th><th>Rating</th><th>Participation</th><th>Status</th><th>Action</th></tr>";
foreach ($people as $email => &$person) {
	echo '<tr><td>'.$person['email'].'</td><td>'.$person['name'].'</td><td>'.$person['org'].'</td>';
	echo '<td>'.(isset($person['gdocs_ref']) ? $person['gdocs_ref'] : '-') . '</td>';
	echo '<td>'.(isset($person['gdocs_rating']) ? $person['gdocs_rating'] : '-') . '</td>';
	echo '<td>'.(isset($person['participation']) ? $person['participation'] : '-') . '</td>';

	if (!empty($person['ebt_datepurchased'])) {
		echo '<td>Attending</td><td>-</td>';
		$stats['attending']++;

	} elseif (!empty($person['dateexpired'])) {
		echo '<td>Expired</td><td>-</td>';
		$stats['expired']++;

	} elseif (!empty($person['code']) and !empty($person['dateinvited'])) {
		echo '<td>Invited ('.$person['code'].')</td>';
		$stats['invited']++;

		// If their invite is nearing expiry, send a reminder (only if they are 5*)
		$datesent = new DateTime($person['dateinvited'], $tzutc);
		$reminderhorizon = new DateTime($reminderperiod.' days ago', $tzutc);
		if ($datesent < $reminderhorizon and empty($person['datereminded']) and $person['gdocs_rating'] >= 5) {

			// Construct the message parts
			$placeholders = array('{email}', '{code}');
			$replacements = array($person['email'], $person['code']);
			$htmloutput = str_replace($placeholders, $replacements, $reminderbody_html);
			$textoutput = str_replace($placeholders, $replacements, $reminderbody_text);

			// Send
			sendEmail($person['email'], 'Reminder: Edge conf invite', $textoutput, $htmloutput);
			$person['datereminded'] = time();

			echo '<td>Sent reminder</td>';
		} else {
			echo '<td>-</td>';
		}

	} elseif (isset($person['gdocs_rating']) and $person['gdocs_rating'] < $minimumrating) {
		echo '<td>Waitlist</td><td>-</td>';
		$stats['waitlist']++;

	} elseif (isset($person['gdocs_ref'])) {

		$code = $db->querySingle('SELECT c.code FROM codes c LEFT JOIN invites i ON c.code=i.code WHERE i.code IS NULL LIMIT 1;');
		$numinvited = $db->querySingle('SELECT COUNT(*) FROM invites WHERE ebt_datepurchased IS NOT NULL or (dateinvited IS NOT NULL AND dateexpired IS NULL)');
		if (!$code) {
			echo '<td>Eligible</td><td>Out of promo codes</td>';
		} elseif ($numinvited > $eventcapacity) {
			echo '<td>Eligible</td><td>Eligible to invite but event over capacity ('.$numinvited.' > '.$eventcapacity.')</td>';
		} else {
			echo '<td>Invited</td><td>Invitation sent with code '.$code.'</td>';
			$stats['invited']++;

			$htmloutput = str_replace(array('{email}', '{code}'), array($row[1], $code), $emailbody_html);
			$textoutput = str_replace(array('{email}', '{code}'), array($row[1], $code), $emailbody_text);

			sendEmail($person['email'], 'Invite to Edge conf', $textoutput, $htmloutput);

			$person['dateinvited'] = time();
			$person['code'] = $code;
		}
	} else {
		echo '<td>Unknown</td><td>-</td>';
	}
	echo '</tr>';
	updatePerson($person);
	flush();
}
echo "</table>\n";


/* Output stats */

var_dump($stats);





function sendEmail($to, $subj, $text, $html) {
	global $overrideemail;

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
	if ($overrideemail) $to = $overrideemail;
	return mail($to, $subj, $email, $mimeheaders, '-f noreply@labs.ft.com');
}

function updatePerson($person) {
	global $db;
	$fields = array('email', 'name', 'org', 'dateinvited', 'datereminded', 'participation', 'gdocs_ref', 'gdocs_rating', 'code', 'ebt_datepurchased', 'dateexpired');
	foreach ($fields as $field) {
		if (!isset($person[$field])) $person[$field] = null;
	}

	$db->query('INSERT INTO invites SET {email}, {name}, {org}, {dateinvited|date}, {datereminded|date}, {participation}, {gdocs_ref}, {gdocs_rating}, {code}, {ebt_datepurchased|date}, {dateexpired|date} ON DUPLICATE KEY UPDATE {name}, {org}, {dateinvited|date}, {datereminded|date}, {participation}, {gdocs_ref}, {gdocs_rating}, {code}, {ebt_datepurchased|date}, {dateexpired|date}', $person);
}
