#!/usr/bin/php
<?php

require __DIR__."/../../vendor/autoload.php";

$app = new ServicesContainer();

$ALERT_SOON = 1;
$ALERT_NOW = 2;

// Find any sessions that have just started
$sessions = $app->db->queryAllRows("SELECT s.start_time, s.name, s.room, s.type, e.time_zone FROM sessions s INNER JOIN events e ON s.event_id=e.id WHERE s.start_time < NOW() AND s.start_time > (NOW() - INTERVAL 2 MINUTE) AND s.alert_flags & %d = 0", $ALERT_NOW);
if ($sessions) {
	$op = 'Starting now:';
	$ids = array();
	foreach ($sessions as $s) {
		$tz = new \DateTimeZone($session['time_zone']);
		$op .= "\n    •  `".$s['start_time']->setTimeZone($tz)->format('H:i')."`  *" . $s['name'] . "*";
		if ($s['room']) {
			$op .= " (in " . $s['room'] . ")";
		}
		if (count($ids) > 1) {
			$op .= "\nDon't join late: if you've been delayed please join the session in the main space.";
		}
		$ids[] = $s['id'];
	}
	$app->db->query('UPDATE sessions SET alert_flags = alert_flags | %d WHERE id IN %d|list', $ALERT_NOW, $ids);
	send($op);
}

// Find any sessions that are about to start
$sessions = $app->db->queryAllRows("SELECT * FROM sessions WHERE start_time > NOW() AND start_time < (NOW() + INTERVAL 15 MINUTE) AND alert_flags & %d = 0", $ALERT_SOON);
if ($sessions) {
	$op = 'Coming up next:';
	$ids = array();
	foreach ($sessions as $s) {
		$tz = new \DateTimeZone($session['time_zone']);
		$op .= "\n    •  `".$s['start_time']->setTimeZone($tz)->format('H:i')."`  *" . $s['name'] . "*";
		if ($s['room']) {
			$op .= " (in " . $s['room'] . ")";
		}
		$ids[] = $s['id'];
	}
	if (count($ids) > 1) {
		$op .= "\nSessions that are not in the main space have limited capacity, and will not admit more delegates when full, so please arrive in plenty of time for your session.  This also avoids causing disruption to sessions that have already started.";
	}
	$app->db->query('UPDATE sessions SET alert_flags = alert_flags | %d WHERE id IN %d|list', $ALERT_SOON, $ids);
	send($op);
}



function send($content) {
	global $app;

	$http = new \HTTP\HTTPRequest($app->config->slack->incoming_webhook);
	$http->setMethod('POST');
	$http->setRequestBody(json_encode(array(
		'channel' => '#'.$app->config->slack->announce_channel,
		'text' => $content
	)));
	try {
		$http->send();
		echo $content."\n";
	} catch(Exception $e) {}
}
