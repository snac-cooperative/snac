<?php

/**
 * API Key Generator 
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2019 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\server\authentication;

/**
 * API Generator Class
 *
 * Static methods to generate secure API keys
 */
class APIKeyGenerator {

    /**
     * Generate API Key
     *
     * This method generates an API key by sampling random bytes, combined with the
     * time and userID to produce a unique string that is then hashed and base64-encoded
     * to produce a unique key.  This is essentially a long password and should NOT be stored
     * locally.
     *
     * @param int $userid The user's id
     * @return string The generated API key
     */
    public static function generateKey($userid) {

        $random = random_bytes(1024);
        $time = time();

        $seed = $random . $time . $userid;

        $inter = sha1($seed);

        $real = substr(base64_encode($inter), 0, -2);

        return $real;
    }

}


