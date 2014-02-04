#!/usr/bin/php
<?php

require __DIR__."/../../vendor/autoload.php";

$app = new ServicesContainer();

$res = $app->db->query('SELECT pe.email, e.mailchimp_group, IF(a.ticket_type IS NULL, %s, IF(pa.role=%s, %s, IF(pa.role=%s, %s, %s))) as groupvalue FROM attendance a INNER JOIN people pe ON a.person_id=pe.id INNER JOIN events e ON a.event_id=e.id LEFT JOIN participation pa ON a.person_id=pa.person_id GROUP BY a.event_id, pe.id ORDER BY email', "Registrant", "Moderator", "Moderator", "Panellist", "Panellist", "Delegate");

$groupings = array();
$email = '';
foreach ($res as $person) {
	if ($email !== $person['email']) {
		if ($email) send($email, $groupings);
		$email = $person['email'];
		$groupings = array();
	}
	$groupings[] = array('name'=>$person['mailchimp_group'], 'groups'=>$person['groupvalue']);
}
send($email, $groupings);

function send($email, $groups) {
	global $app;
	echo $email."\n";
	$merge_vars = array('GROUPINGS'=> $groups);
	$retval = $app->mailchimp->listSubscribe($app->config->mailchimp->list_id, $email, $merge_vars, 'html', false, true, true, false);
	if ($retval !== true) {
		echo "\nStopping due to a Mailchimp error:\n";
		var_dump($retval);
		exit;
	}
}
