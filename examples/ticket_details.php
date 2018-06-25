<?php
// Run this script using: php examples/ticket_details.php <YOUR-TOKEN>

require __DIR__.'/../vendor/autoload.php';

$authentication = new Exonet\Api\Auth\PersonalAccessToken($argv[1]);

$exonetApi = new Exonet\Api\Client($authentication);

/*
 * Get a single ticket resource. Because depending on who is authorized, the ticket IDs change, all tickets are
 * retrieved with a limit of 1. From this result, the first ticket is used. In a real world scenario you would
 * call something like `$ticket = $exonetApi->resource('tickets')->get('VX09kwR3KxNo');` to get a single ticket
 * by it's ID.
 */
$tickets = $exonetApi->resource('tickets')->get();

// Show this message when there are no tickets available.
if (empty($tickets)) {
    echo 'There are no tickets available';
    die();
}

$ticket = $tickets[0];

echo sprintf("\nTicket id:\t\t%s", $ticket->id);
echo sprintf("\nTicket subject:\t\t%s", $ticket->last_message_subject);
echo sprintf("\nCreated at:\t\t%s", $ticket->created_date);
echo sprintf("\nLast message date:\t%s", date('l jS \of F Y H:i:s', strtotime($ticket->last_message_date)));
echo "\n";

// Get the emails in the ticket.
$emails = $ticket->emails()->get();

foreach ($emails as $email) {
    echo sprintf("\nFrom: %s - To: %s - Subject: %s", $email->from, $email->to, $email->subject ?? '(no subject)');
}
echo "\n";
