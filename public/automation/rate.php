<style>
body { font-family: sans-serif; margin: 15px; }
table { border: 1px solid #aaa; border-collapse: collapse; margin: 1em 0;}
th, td { border: 1px solid #aaa; padding: 3px 5px; text-align: left; font-size:12px; }
.error { color: red; }
</style>
<h1>Rate new proposals</h1>
<p>Showing unrated proposals for the next upcoming Edge event</p>
<form method='post' action='<?php echo $_SERVER['REQUEST_URI']; ?>'>
<?php

require_once '../../app/global';

$user = adminOnly();
$db = new MySQLConnection('localhost', MYSQL_USER, MYSQL_PASS, MYSQL_DBNAME);


if (!empty($_POST)) {
	$idx = 0;
	while (!empty($_POST['email_'.$idx])) {
		if (!empty($_POST['sessionid_'.$idx]) and isset($_POST['rating_'.$idx])) {
			$db->query("UPDATE participation SET rating=%d, ratingauthor=%s, ratingdate=NOW() WHERE sessionid=%d AND email=%s", $_POST['rating_'.$idx], $user['email'], $_POST['sessionid_'.$idx], $_POST['email_'.$idx]);
		}
		$idx++;
	}
	header('Location: '.$_SERVER['REQUEST_URI']);
	exit;
}


$idx = 0;
$sessions = $db->query('SELECT * FROM sessions WHERE eventid=%d ORDER BY starttime', EVENT_ID);
foreach ($sessions as $session) {
	$stats = $db->queryRow('SELECT SUM(IF(tickettype IS NOT NULL, 1, 0)) as attending, SUM(IF(invitedatesent IS NOT NULL AND tickettype IS NULL, 1, 0)) as invited, SUM(IF(tickettype IS NULL AND invitedatesent IS NULL, 1, 0)) as waitlist, AVG(rating) as avgrating FROM attendance a INNER JOIN participation p ON a.email=p.email WHERE p.sessionid=%d', $session['id']);
	$newproposals = $db->query('SELECT pe.email, pe.org, pa.proposal FROM people pe INNER JOIN participation pa ON pe.email=pa.email WHERE pa.sessionid=%d AND role=%s AND rating IS NULL', $session['id'], 'Delegate');
	echo '<h2>'.$session['name'].'</h2>';
	echo '<p>Attending: '.$stats['attending'].', Invited: '.$stats['invited'].', Waitlist: '.$stats['waitlist'].', Avg rating: '.$stats['avgrating'].'</p>';
	echo '<table>';
	echo '<thead><tr><th>Org</th><th>Proposal</th><th>Rating</th></tr></thead>';
	echo '<tbody>';
	foreach ($newproposals as $proposal) {
		echo '<tr><td>'.e($proposal['org']).'</td><td>'.e($proposal['proposal']).'</td>';
		echo '<td><input type="text" name="rating_'.$idx.'" /><input type="hidden" name="email_'.$idx.'" value="'.e($proposal['email']).'" /><input type="hidden" name="sessionid_'.$idx.'" value="'.$session['id'].'" /></td></tr>';
		$idx++;
	}
	echo '</tbody></table>';
}

?>
<input type='submit' />
</form>
