<?php
/**
 * Mailer class 
 *
 * Contains the mailer information
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

namespace snac\server\mailer;

use \snac\Config as Config;

/**
 * Mailer Class
 *
 * Class used to send emails from the system
 *
 * @author Robbie Hott
 */
class Mailer {

    private $mailer;

    private $logger;

    private $fromName;

    private $fromEmail;

    public function __construct() {
        global $log;

        // create a log channel
        $this->logger = new \Monolog\Logger('Mailer');
        $this->logger->pushHandler($log);
        
        $this->mailer = new \PHPMailer();
        if (\snac\Config::$EMAIL_SMTP) {
            $this->mailer->isSMTP();                                                // Set mailer to use SMTP
            $this->mailer->SMTPAuth = \snac\Config::$EMAIL_CONFIG["smtp_auth"];     // Enable SMTP authentication
            $this->mailer->Username = \snac\Config::$EMAIL_CONFIG["username"];      // SMTP username
            $this->mailer->Password = \snac\Config::$EMAIL_CONFIG["password"];      // SMTP password
            $this->mailer->SMTPSecure = \snac\Config::$EMAIL_CONFIG["security"];    // Enable TLS encryption, `ssl` also accepted
        }
        $this->mailer->Host = \snac\Config::$EMAIL_CONFIG["host"];              // Specify main and backup SMTP servers
        $this->mailer->Port = \snac\Config::$EMAIL_CONFIG["port"];              // TCP port to connect to

        $this->fromName = \snac\Config::$EMAIL_CONFIG["from_name"];
        $this->fromEmail = \snac\Config::$EMAIL_CONFIG["from_email"];
    }

    public function sendUserMail($toUser, $subject, $body) {
        $this->mailer->setFrom($this->fromEmail, $this->fromName);
        $this->mailer->addAddress($toUser->getEmail(), $toUser->getFullName()); // Add a recipient
        //$this->mailer->addAddress('ellen@example.com');                       // Name is optional
        //$this->mailer->addReplyTo('info@example.com', 'Information');
        //$this->mailer->addCC('cc@example.com');
        //$this->mailer->addBCC('bcc@example.com');

        //$this->mailer->addAttachment('/var/tmp/file.tar.gz');                 // Add attachments
        //$this->mailer->addAttachment('/tmp/image.jpg', 'new.jpg');            // Optional name
        $this->mailer->isHTML(true);                                            // Set email format to HTML

        $this->mailer->Subject = $subject;

        // Use Twig to style the message body using the snac style
        $loader = new \Twig_Loader_Filesystem(\snac\Config::$EMAIL_TEMPLATE_DIR);
        $twig = new \Twig_Environment($loader, array());
        $htmlBody = $twig->render("default.html", array("body" => $body));
        $this->mailer->Body    = $htmlBody;
        $textBody = $twig->render("default.txt", array("body" => \Html2Text\Html2Text::convert($body)));
        $this->mailer->AltBody    = $textBody;


        if(!$this->mailer->send()) {
            $this->logger->addDebug('Message could not be sent: ' . $this->mailer->ErrorInfo);
        } 
    }
    
    public function sendUserMessage(&$message) {
        $this->logger->addDebug('Trying to send message', $message->toArray());
        $this->mailer->setFrom($this->fromEmail, $this->fromName);
        $this->mailer->addAddress($message->getToUser()->getEmail(), $message->getToUser()->getFullName()); // Add a recipient
        //$this->mailer->addAddress('ellen@example.com');                       // Name is optional
        //$this->mailer->addReplyTo('info@example.com', 'Information');
        //$this->mailer->addCC('cc@example.com');
        //$this->mailer->addBCC('bcc@example.com');

        //$this->mailer->addAttachment('/var/tmp/file.tar.gz');                 // Add attachments
        //$this->mailer->addAttachment('/tmp/image.jpg', 'new.jpg');            // Optional name
        $this->mailer->isHTML(true);                                            // Set email format to HTML

        $this->mailer->Subject = "SNAC Mail: " . $message->getSubject();

        $this->logger->addDebug('Building template message');
        // Use Twig to style the message body using the snac style
        $loader = new \Twig_Loader_Filesystem(\snac\Config::$EMAIL_TEMPLATE_DIR);
        $twig = new \Twig_Environment($loader, array());
        $this->logger->addDebug('Building HTML template message');
        $htmlBody = $twig->render("default.html", $message->toArray());
        $this->mailer->Body    = $htmlBody;
        $this->logger->addDebug('Building TXT template message');
        $textMessage = $message->toArray();
        $textMessage["body"] = \Html2Text\Html2Text::convert($message->getBody());
        $textBody = $twig->render("default.txt", $textMessage);
        $this->mailer->AltBody    = $textBody;

        $this->logger->addDebug('Sending message');

        if(!$this->mailer->send()) {
            $this->logger->addDebug('Message could not be sent: ' . $this->mailer->ErrorInfo);
        } 
    }
}

