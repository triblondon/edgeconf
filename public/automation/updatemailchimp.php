<h1>Updating Mailchimp</h1>
<ul>
<?php
require_once '../../app/global';

adminOnly();

$db = new MySQLConnection('localhost', MYSQL_USER, MYSQL_PASS, MYSQL_DBNAME);
$db->setQueryLogging(true);

$sessions = $db->queryLookupTable('SELECT id as k, name as v FROM sessions WHERE eventid=%d ORDER BY starttime', EVENT_ID);

$api = new MCAPI(MC_KEY);
$res = $db->query('SELECT a.email, a.tickettype, SUM(IF(p.role=%s, 1, 0)) as ispanelist, SUM(IF(p.role=%s, 1, 0)) as ismod FROM attendance a LEFT JOIN participation p ON a.email=p.email AND p.sessionid IN %d|list WHERE a.eventid=%d GROUP BY a.email', "Panelist", "Moderator", array_keys($sessions), EVENT_ID);
foreach ($res as $person) {
	if (!$person['tickettype']) {
		$group = 'Registrant';
	} else if ($person['ismod']) {
		$group = 'Moderator';
	} else if ($person['ispanelist']) {
		$group = 'Panellist';
	} else {
		$group = 'Delegate';
	}

	$merge_vars = array(
		'GROUPINGS'=> array(
			array('name'=>MC_GROUP, 'groups'=>$group),
		)
	);
	$retval = $api->listSubscribe(MC_LIST, $person['email'], $merge_vars, 'html', false, true, true, false);
	echo '<li>'.$person['email'].' set to '.$group.'</li>';
	flush();
	ob_flush();
}
?>
</ul>
<p>Done.</p>
