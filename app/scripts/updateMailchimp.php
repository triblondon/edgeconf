#!/usr/bin/php
<?php

require __DIR__."/../../vendor/autoload.php";

/**
 * This script performs two steps.
 * 1) Push all persons from our database to MailChimp.
 * 2) Update all the other members - as they did not
 * participate, clear their groups.
 *
 * At the end every MailChimp member should have the
 * correct group values.
 */

$app = new ServicesContainer();

$sql  = 'SELECT pe.email, e.mailchimp_group,' .
        ' IF(a.ticket_type IS NULL, %s, IF(a.ticket_type=%s, %s, IF(pa.role=%s, %s, IF(pa.role=%s OR pa.role=%s, %s, %s)))) as groupvalue ' .
        ' FROM people pe' .
        ' INNER JOIN attendance a' .
        ' ON pe.id = a.person_id' .
        ' INNER JOIN events e' .
        ' ON a.event_id=e.id' .
        ' LEFT JOIN participation pa' .
        ' ON pe.id = pa.person_id AND session_id IN (SELECT id FROM sessions s WHERE event_id=e.id) AND pa.role <> %s AND pa.role IS NOT NULL AND pa.panel_status = %s' .
        ' GROUP BY pe.id, e.mailchimp_group' .
        ' ORDER BY email';

$res = $app->db->query($sql, "Registrant", "Partyist", "Partyist", "Moderator", "Moderator", "Panelist", "Speaker", "Panelist", "Delegate", "Delegate", "Confirmed");

$email_list = array();
$groupings = array();
$summary = array();
$email = '';

foreach ($res as $person) {

    // Keep a reference to all processed addresses for
    // the second step of the script.

    $email_list[] = normaliseEmail($person['email']);

    // Build group list.

    # if ($email != 'joelzimmer@gmail.com') continue;
    if ($email !== $person['email']) {

        if ($email) {
            send($email, $groupings);
        }

        $email = $person['email'];
        $groupings = array();
    }

    $groupings[] = array('name'=>$person['mailchimp_group'], 'groups'=>$person['groupvalue']);
    $summary[$person['email']][$person['mailchimp_group']] = $person['groupvalue'];
}

send($email, $groupings);
# print_r($summary);


// Now we have all users of our database at MailChimp, but in order to
// have a consistend dataset, we compare the other way round as well.
// This way is pretty easy, as we have all participation data in our
// database, all other list-members did not participate - which means
// we need to make sure they are marked as such by having just empty
// groups.

$emptyGroups = array();

$res = $app->db->query('SELECT `mailchimp_group` FROM `events`;');

foreach ($res as $row) {
    $emptyGroups[] = array(
        'name' => $row['mailchimp_group'],
        'groups' => ''
    );
}

// Get all MailChimp list members

$list_id = $app->config->mailchimp->list_id;
$list = $app->mailchimp->listMembers($list_id, $status='subscribed', $since=NULL, $start=0, $limit=15000, $sort_dir='ASC');

if (!isset($list['total']) || !isset($list['data'])) {
    throw new Exception('Unexpected result when querying MailChimp!');
}

echo 'The MailChimp list holds ' . $list['total'] . ' subscribers.' . "\n";

foreach ($list['data'] as $member) {
    $memberInfo = $app->mailchimp->listMemberInfo($list_id, $member['email']);

    if (!isset($memberInfo['success']) ||
        !isset($memberInfo['data'][0]) ||
        ($memberInfo['success'] !== 1)
    ) {
        echo 'An error occured while loading member: ' . $member['email'] . '.'. "\n";
        continue;
    }

    $memberData = $memberInfo['data'][0];
    $memberEmail = $memberData['email'];

    if (in_array(normaliseEmail($memberEmail), $email_list)) {

        // This address has already been updated in step 1.

        continue;
    }

    if (!isset($memberData['merges']['GROUPINGS'])) {
        echo 'Unexpected member info of member: ' . $memberData['id'] . '(' . $memberEmail .')' . "\n";
    }

    $updateRequired = FALSE;

    foreach ($memberData['merges']['GROUPINGS'] as $group) {

        // Check if there is some status set for this member.

        if (strlen($group['groups']) !== 0) {
            $updateRequired = TRUE;
        }
    }

    if ($updateRequired === TRUE) {
        send($memberEmail, $emptyGroups);
    }
}


/**
 * Add a new Subscriber to the list or update an existing one.
 */

function send($email, $groups)
{
    global $app;
    echo $email."\n";
    $merge_vars = array('GROUPINGS'=> $groups);
    $retval = $app->mailchimp->listSubscribe($app->config->mailchimp->list_id, $email, $merge_vars, 'html', false, true, true, false);

    if ($retval !== TRUE) {
        echo "\nStopping due to a Mailchimp error:\n";
        var_dump($groups);
        var_dump($retval);
        var_dump($app->mailchimp->errorMessage);
        // exit;
    }
}


/**
 * Normalise email addresses to allow comparison.
 *
 * @todo We have this code multiple times.
 */

function normaliseEmail($email)
{
    // Get the email address

    list($username, $domain) = explode('@', $email, 2);

    // Domain is always case insensitive, even for non-gmail addresses

    $domain = strtolower($domain);

    // Rewrites

    if ($domain === 'googlemail.com') {
        $domain = 'gmail.com';
    }

    if ($domain === 'gmail.com') {
        $username = strtolower(str_replace('.', '', $username));
    }

    // Remove everything after '+'

    return preg_replace('/\+.*$/', '', $username) . '@' . $domain;
}
