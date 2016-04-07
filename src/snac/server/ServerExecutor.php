<?php

/**
 * Server Executor Class File
 *
 * Contains the ServerExector class that performs all the tasks for the main Server
 *
 * License:
 *
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2016 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\server;

use League\OAuth2\Client\Token\AccessToken;
/**
 * Server Executor Class
 *
 *  Contains functions that the Server's workflow engine needs to complete its work.
 *
 * @author Robbie Hott
 */
class ServerExecutor {

    /**
     * @var \snac\server\database\DBUtil Constellation Storage Object
     */
    private $cStore = null;
    
    /**
     * @var \snac\server\database\DBUser User Storage Object
     */
    private $uStore = null;
    
    /**
     * @var \snac\data\User Current user object
     */
    private $user = null;
    

    /**
     * @var \Monolog\Logger $logger the logger for this server
     */
    private $logger;
    
    public function __construct() {
        global $log;
        
        $this->cStore = new \snac\server\database\DBUtil();
        $this->uStore = new \snac\server\database\DBUser();
        
        $this->user = $this->getDefaultPublicUser();
        
        //$this->cStore->setUser($this->user);
        
        // create a log channel
        $this->logger = new \Monolog\Logger('ServerExec');
        $this->logger->pushHandler($log);
    }
    
    /**
     * Authenticate User
     * 
     * Authenticates the user by checking the user store (dbuser)
     * 
     * @param string[] $user User information to check
     * @return boolean true if user authenticated, false if not
     */
    public function authenticateUser($user) {
        if ($user != null) {
            $this->logger->addDebug("Attempting to authenticate user", $user);
            
            $tmpUser = new \snac\data\User($user);
            
            // Google OAuth Settings (from Config)
            $clientId     = \snac\Config::$OAUTH_CONNECTION["google"]["client_id"];
            $clientSecret = \snac\Config::$OAUTH_CONNECTION["google"]["client_secret"];
            // Change this if you are not using the built-in PHP server
            $redirectUri  = \snac\Config::$OAUTH_CONNECTION["google"]["redirect_uri"];
            // Initialize the provider
            $provider = new \League\OAuth2\Client\Provider\Google(compact('clientId', 'clientSecret', 'redirectUri'));
            
            try {
                $this->logger->addDebug("Trying to connect to OAuth2 Server to get user details");
                
                $accessToken = new AccessToken($tmpUser->getToken());
                
                $ownerDetails = $provider->getResourceOwner($accessToken);
                
                if ($ownerDetails->getEmail() != $tmpUser->getEmail()) {
                    // This user's token doesn't match the user's email
                    $this->logger->addDebug("Email mismatch from the user and OAuth details");
                    return false;
                }
                $this->logger->addDebug("Successfully got user details from OAuth2 Server");
            } catch (\Exception $e) {
                $this->logger->addDebug("Could not get user details from OAuth2 Server: ".$e->getMessage());
                return false;
            }
            
            //$this->user = $this->uStore->readUser($tmpUser);
            
            //$this->cStore->setUser($this->user);
            return true;
        }
        
        // Authentication works if no user, because we have the public user
        return true;
    }
    
    /**
     * Get Public User
     * 
     * Gets the default public user, which only has permission to view and no dashboard permissions
     * 
     * @return \snac\data\User Public user
     */
    public function getDefaultPublicUser() {
        //$user = $this->uStore->getPublicUser();
        $user = new \snac\data\User();
        
        return $user;
    }
}
