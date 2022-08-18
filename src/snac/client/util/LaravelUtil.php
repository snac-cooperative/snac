<?php
/**
 * Laravel Connect Util
 *
 *
 * @author Joseph Glass
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2022 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\client\util;

/**
 * Laravel Connect Utility Class
 *
 * Util class for querying the SNAC Laravel system from SNAC
 *
 * @author Joseph Glass
 *
 */
class LaravelUtil {


    /**
     * POST Laravel
     *
     * Send a POST request to SNAC-laravel
     *
     * @param string $path URL path (ex. /concepts)
     * @param array $query Array of commands
     * @return array $response
     */
    public function postLaravel($path, $query)
    {
        $payload = json_encode($query);

        $ch = curl_init();
        $options = [
            CURLOPT_URL => \snac\Config::$LARAVEL_URL . $path,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ["X-Requested-With: XMLHttpRequest", "Content-Type:application/json"],
            CURLOPT_RETURNTRANSFER => true
        ];

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($response, true);
        return $response;
    }

    /**
     * Get Laravel
     *
     * Sends a GET request to SNAC-Laravel
     *
     * @param string $path URL path (ex. /concepts)
     * @param array $query Array of commands
     * @return array $response
     */
    public function getLaravel($path, $query)
    {
        $params = http_build_query($query);

        $ch = curl_init();
        $options = [
            CURLOPT_URL => \snac\Config::$LARAVEL_URL . $path . "?" . $params,
            CURLOPT_HTTPHEADER => ["X-Requested-With: XMLHttpRequest", "Content-Type:application/json"],
            CURLOPT_RETURNTRANSFER => true
        ];
        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($response, true);
        return $response;
    }


}
