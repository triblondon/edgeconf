#!/usr/bin/php
<?php

require __DIR__."/../../vendor/autoload.php";

/**
 * Canonicalise GMail addresses: googlemail and gmail are the same,
 * dots in username are ignored. Usernames are case insensitive.
 *
 * We keep different versions for different purposes in memory:
 * 1) Original (used for output)
 * 2) No periods (used to save in database)
 * 3) Nothing after '+' (used to find duplicates, manual action required)
 */

$app = new ServicesContainer();

$app->db->query('START TRANSACTION');
$res = $app->db->query('SELECT people.id, people.email FROM edgeconf.people');

$trackDuplicates = array();

foreach ($res as $row) {

    $email = $row['email'];

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

    // Remove everything after '+' - just required
    // to detect duplicates; like foo+edge1, foo+edge2,…

    $usernameStripped = preg_replace('/\+.*$/', '', $username);

    $emailNormalised = $usernameStripped . '@' . $domain;
    $emailDb = $username . '@' . $domain;

    // Keep track

    if (!isset($trackDuplicates[$emailNormalised])) {
        $trackDuplicates[$emailNormalised] = array();
    }

    $trackDuplicates[$emailNormalised][] = $email;

    if ($email !== $emailDb) {

        // Update email address

        echo 'Normalised "' . $email . '" to "' . $emailDb . '".' . "\n";
        $app->db->querySingle('UPDATE `people` SET {email} WHERE {id}', array('email' => $emailDb, 'id' => $row['id']));
    }
}

$app->db->query('COMMIT');

// Print summary

echo "\n";

$total = 0;

foreach ($trackDuplicates as $emailNormalised => $stack) {
    $stackSize = count($stack);
    $total += $stackSize;

    if ($stackSize === 1) {

        // No duplicates.

        continue;
    }

    echo 'Found multiple matches for "' . $emailNormalised . '": ' . implode(', ', $stack) . '!' . "\n";
}

echo "\n";
echo 'Total number of different addresses: ' . count($trackDuplicates) . ' of ' . $total . "\n";
