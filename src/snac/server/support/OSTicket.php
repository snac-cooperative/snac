<?php
/**
 * OS Ticket class 
 *
 * Contains the ticket submission information
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

namespace snac\server\support;

use \snac\Config as Config;

/**
 * OS Ticketer Class
 *
 * Class used to submit support tickets from the system
 *
 * @author Robbie Hott
 */
class OSTicket {

    /**
     * @var \Monolog\Logger The logger instance for this class
     */
    private $logger;

    /**
     * Constructor
     */
    public function __construct() {
        global $log;

        // create a log channel
        $this->logger = new \Monolog\Logger('OSTicket');
        $this->logger->pushHandler($log);
    }

    /**
     * Submit Message as Ticket
     *
     * Submits a message object as a ticket to the OS Ticket system.
     *
     * @param \snac\data\Message $message The message to submit
     */
    public function submitMessageAsTicket(&$message) {

        $payload = array();

        //TODO We should have the WebUI send the user information through the message as well, rather than just the email.

        $this->logger->addDebug('Trying to submit message', $message->toArray());
        if ($message->getFromString() !== null) {
            list($name, $email, $junk) = explode("|", $message->getFromString());
            if ($email == null)
                $email = "unknown";
            $payload["email"] = $email;
            $payload["name"] = $name;
        } else {
            $payload["email"] = $message->getFromUser()->getEmail();
            $payload["name"] = $message->getFromUser()->getFullName();
        }
        $payload["subject"] = $message->getSubject();
        $payload["alert"] = true;
        $payload["autorespond"] = true;
        $payload["message"] = \Html2Text\Html2Text::convert($message->getBody());

        if ($message->getAttachmentContent() && $message->getAttachmentFilename() == "screenshot.png") {
            $payload["attachments"] = array(
                array($message->getAttachmentFilename() => $message->getAttachmentContent())
            );
        }
        
        $this->logger->addDebug("Sending the following osTicket query", $payload);
        // Encode the query as json
        $data = json_encode($payload);

        try {
            // Use CURL to send request to the internal server
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, \snac\Config::$OSTICKET_URL);
            curl_setopt($ch, CURLOPT_HTTPHEADER,
                    array (
                            'Content-Type: application/json',
                            'Content-Length: ' . strlen($data),
                            'X-API-Key: ' . \snac\Config::$OSTICKET_KEY
                    ));
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $this->logger->addDebug("Got the following result: $response");
            if ($responseCode == 201)
                return true;
            $this->logger->addDebug("Errored out on response code $responseCode");
        } catch (\Exception $e) {
            $this->logger->addDebug("CURL ERROR: " . $e->getTraceAsString());
        }
    }
}

