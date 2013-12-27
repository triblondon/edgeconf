<?php
require_once '../../app/global';

adminOnly();

$db = new MySQLConnection('localhost', MYSQL_USER, MYSQL_PASS, MYSQL_DBNAME);
$db->setQueryLogging(true);


/* Download Google Docs data */

echo "<p>Downloading application data from Google Drive</p>";
flush();
$http = new HTTPRequest(GSHEET_REG);
$resp = $http->send();

echo "<p>Decoding CSV</p>";
flush();
file_put_contents('/tmp/edgecsv', $resp->getBody());
$csv = new Coseva\CSV('/tmp/edgecsv');
$csv->parse();
foreach ($csv as $row) {
	if ($row[0] == 'Ref' or empty($row[1])) continue;
	$data = array(
		'email' => strtolower(trim($row[1])),
		'givenname' => ucwords(trim($row[3])),
		'familyname'=> ucwords(trim($row[2])),
		'org' => trim($row[4]),
		'rating' => trim($row[7]),
		'ratingauthor' => 'andrew.betts@gmail.com',
		'proposal' => trim($row[6]),
		'eventid' => EVENT_ID
	);
	$db->query('INSERT INTO people SET {email}, {givenname}, {familyname}, {org} ON DUPLICATE KEY UPDATE {givenname}, {familyname}, {org}', $data);
	$db->query('INSERT IGNORE INTO attendance SET {email}, {eventid}', $data);

	$sessions = explode(', ', $row[5]);
	foreach ($sessions as $sessionname) {
		$data['sessionid'] = $db->querySingle('SELECT id FROM sessions WHERE eventid=%d AND name=%s', EVENT_ID, $sessionname);
		if ($data['sessionid']) {
			$db->query('INSERT INTO participation SET {email}, {sessionid}, {proposal}, {rating}, {ratingauthor}, ratingdate=NOW(), datecreated=NOW() ON DUPLICATE KEY UPDATE {proposal}, {rating}, {ratingauthor}, ratingdate=NOW()', $data);
		}
	}
	echo '<p>'.$data['email']."</p>";
}

/*

TODO: Get session-by-session speaking data from Wes

echo "<p>Downloading special invites from Google Drive</p>";
flush();
$http = new HTTPRequest(GSHEET_SPECIALINVITE);
$resp = $http->send();

echo "<p>Decoding CSV</p>";
flush();
file_put_contents('/tmp/edgecsv', $resp->getBody());
$csv = new Coseva\CSV('/tmp/edgecsv');
$csv->parse();
foreach ($csv as $row) {
	if ($row[0] == 'Name' or empty($row[2])) continue;
	$name = explode(' ', $row[0], 2);
	$data = array(
		'email' => strtolower(trim($row[2])),
		'givenname' => ucwords($name[0]),
		'familyname' => ucwords($name[1]),
		'org' => $row[1],
	);

	$db->query('INSERT INTO people SET {email}, {givenname}, {familyname}, {org} ON DUPLICATE KEY UPDATE {givenname}, {familyname}, {org}', $data);
	$db->query('INSERT IGNORE INTO attendance SET {email}, {eventid}', $data);
	$db->query('INSERT INTO activity ... ')
}
*/
