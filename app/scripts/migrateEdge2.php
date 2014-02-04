#!/usr/bin/php
<?php

require __DIR__."/../../vendor/autoload.php";

$app = new ServicesContainer();

$app->db->query('START TRANSACTION');

$eventid = 2;

$res = $app->db->query('SELECT * FROM edge2.invites');
foreach ($res as $row) {

	// Get the email address
	list($username, $domain) = explode('@', $row['email'], 2);

	// Canonicalise GMail addresses: googlemail and gmail are the same,
	// dots in username are ignored, as is anything after a +.  Usernames
	// are case insensitive
	if ($domain == 'googlemail.com') $domain = 'gmail.com';
	if ($domain == 'gmail.com') {
		$username = strtolower(str_replace('.', '', $username));
		$username = preg_replace('/\+.*$/', '', $username);
	}

	// Domain is always case insensitive, even for non-gmail addresses
	$row['email'] = $username.'@'.strtolower($domain);

	// Try and find matching person.  If not, create them.
	$personid = $app->db->querySingle('SELECT id FROM people WHERE {email}', $row);
	if (!$personid) {
		list($first, $last) = explode(' ', $row['name']);
		$personins = $app->db->query('INSERT INTO people SET email=%s, given_name=%s, family_name=%s, org=%s, created_at=NOW()', $row['email'], $first, $last, $row['org']);
		$personid = $personins->getInsertId();
	}

	// Create attendence record
	$ticket = (in_array($row['participation'], array('Panellist','Comp','Moderator')) ? 'Comp' : (($row['participation'] == 'Delegate') ? 'Standard' : null));
	echo $row['name'].' ('.$personid.') '.$ticket."\n";
	$app->db->query('REPLACE INTO attendance SET person_id=%d, event_id=%d, invite_code=%s, invite_date_sent=%s|date, invite_date_reminded=%s|date, invite_date_expired=%s|date, ticket_type=%s, ticket_date=%s|date, created_at=NOW()', $personid, $eventid, $row['code'], $row['dateinvited'], $row['datereminded'], $row['dateexpired'], $ticket, $row['ebt_datepurchased']);
}


$app->db->query('COMMIT');
