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

class APIKeyGenerator {

    public static function generateKey($userid) {

        $random = random_bytes(1024);
        $time = time();

        $seed = $random . $time . $userid;

        $inter = sha1($seed);

        $real = substr(base64_encode($inter), 0, -2);

        return $real;
    }

}


