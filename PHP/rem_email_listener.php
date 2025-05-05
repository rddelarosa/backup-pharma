<?php
require ("rem_process_email.php"); 

$hostname = '{imap.gmail.com:993/imap/ssl}INBOX';
$username = 'pharmasync.ph@gmail.com'; 
$password = 'msnh jdhw efvw xnwb';

$inbox = imap_open($hostname, $username, $password) or die('Cannot connect to Gmail: ' . imap_last_error());

$emails = imap_search($inbox, 'UNSEEN');

if ($emails) {
    foreach ($emails as $email_number) {
        $overview = imap_fetch_overview($inbox, $email_number, 0)[0];
        $message = imap_fetchbody($inbox, $email_number, 1.1); 
        if ($message == "") {
            $message = imap_fetchbody($inbox, $email_number, 1);
        }

        $subject = $overview->subject;
        $from = $overview->from;
        $email = extractEmail($from); 

        process_email_command($email, $subject, $message);

        imap_setflag_full($inbox, $email_number, "\\Seen");
    }
}

imap_close($inbox);

function extractEmail($fromHeader) {
    preg_match('/<(.+)>/', $fromHeader, $matches);
    return isset($matches[1]) ? $matches[1] : $fromHeader;
}
?>
