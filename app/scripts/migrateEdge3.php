#!/usr/bin/php
<?php

require __DIR__."/../../vendor/autoload.php";

$app = new ServicesContainer();

$app->db->query('START TRANSACTION');

$app->db->query('TRUNCATE TABLE edgeconf.attendance;');
$app->db->query('TRUNCATE TABLE edgeconf.participation;');
$app->db->query('TRUNCATE TABLE edgeconf.people;');

$app->db->query('INSERT INTO edgeconf.people (email, given_name, family_name, org, travel_origin, created_at, updated_at) SELECT email, givenname, familyname, org, travelorigin, datecreated, datemodified FROM edgeconf_old.people');

$res = $app->db->query('SELECT * FROM edgeconf_old.participation');
foreach ($res as $row) {
	$personid = $app->db->querySingle('SELECT id FROM edgeconf.people WHERE email=%s', $row['email']);
	if (!$personid) exit('No person found for '.$row['email']."\n");
	$app->db->query('INSERT INTO edgeconf.participation SET person_id=%d, session_id=%d, proposal=%s, role=%s, panel_status=%s, rating=%s, rated_by=%s, rating_date=%s, rating_notes=%s, created_at=%s|date, updated_at=%s|date', $personid, $row['sessionid'], $row['proposal'], $row['role'], $row['panelstatus'], $row['rating'], $row['ratingauthor'], $row['ratingdate'], $row['notes'], $row['datecreated'], $row['datemodified']);
}

$res = $app->db->query('SELECT * FROM edgeconf_old.attendance');
foreach ($res as $row) {
	$personid = $app->db->querySingle('SELECT id FROM edgeconf.people WHERE email=%s', $row['email']);
	if (!$personid) exit('No person found for '.$row['email']."\n");
	$app->db->query('INSERT INTO edgeconf.attendance SET person_id=%d, event_id=%d, invite_code=%s, invite_date_sent=%s, invite_date_reminded=%s, invite_date_expired=%s, expenses_travel=%d, expenses_accom=%d, ticket_type=%s, ticket_date=%s, created_at=%s|date, updated_at=%s|date', $personid, $row['eventid'], $row['invitecode'], $row['invitedatesent'], $row['invitedatereminded'], $row['invitedateexpired'], $row['expensestravel'], $row['expensesaccom'], $row['tickettype'], $row['ticketdate'], $row['datecreated'], $row['datemodified']);
}

$app->db->query('COMMIT');
