<?php

namespace Khanhkid\Wakatime;

/**
 * Wakatime API class
 *
 * API Documentation: https://wakatime.com/developers
 * Class Documentation: https://github.com/KhanhKid/WakaTime-API-PHP
 *
 * @author KhanhKid
 * @since 14.10.2017
 * @version 1.0
 * @license BSD http://www.opensource.org/licenses/bsd-license.php
 */
class WakatimeAPI
{
	/**
     * The API base URL.
    */
	const API_URL = 'https://wakatime.com/api/v1/';
	/**
     * The Instagram API Key.
     *
     * @var string
     */
    private $_apikey;
    /**
     * Available scopes.
     *
     * @var string[]
     */
    private $_scopes = array('email', 'read_logged_time', 'write_logged_time', 'read_stats', 'read_teams');
    /**
     * Rate limit.
     *
     * @var int
     */
    private $_xRateLimitRemaining;
    /**
     * API-key Setter
     *
     * @param string $apiKey
     *
     * @return void
     */
    /**
     * Default constructor.
     *
     * @param array|string $config Instagram configuration data
     *
     * @return void
     *
     * @throws \Khanhkid\Wakatime\WakatimeException
     */
    public function __construct($config)
    {
        if (is_array($config)) {
            // if you want to access user data
            $this->setApiKey($config['apiKey']);
        } elseif (is_string($config)) {
            // if you only want to access public data
            $this->setApiKey($config);
        } else {
            throw new WakatimeException('Error: __construct() - Configuration data is missing.');
        }
    }

    /**
     * A user's coding activity for the given time range as an array of summaries segmented by day..
     *
     * @param start (Date) - required - Start date of the time range.
     * @param end (Date) - required - End date of the time range.
	 * @param project (string) - optional - Only show time logged to this project.
	 * @param branches (string) - optional - Only show coding activity for these branches; comma separated list of branch names.
     *
     * @return mixed
     */
    public function getSummaries($start, $end, $project = null, $branches = null)
    {
    	$arrData = array();
    	$arrData['start'] = $start;
    	$arrData['end'] = $end;
    	if(!is_null($project)) $arrData['project'] = $project;
    	if(!is_null($branches)) $arrData['branches'] = $branches;
        return $this->_makeCall('users/current/summaries', $arrData, 'GET');
    }




    public function setApiKey($apiKey)
    {
        $this->_apikey = $apiKey;
    }
    /**
     * Access Token Getter.
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->_apikey;
    }

    /**
     * The call operator.
     *
     * @param string $function API resource path
     * @param array $params Additional request parameters
     * @param string $method Request type GET|POST
     *
     * @return mixed
     *
     * @throws \MetzWeb\Instagram\InstagramException
     */
    protected function _makeCall($function, $params = null, $method = 'GET')
    {
        if (!isset($this->_apikey)) {
                throw new InstagramException("Error: _makeCall() | $function - This method requires an authenticated users access token.");
            }

        $authMethod = '?api_key=' . $this->getApiKey();
        $paramString = null;

        if (isset($params) && is_array($params)) {
            $paramString = '&' . http_build_query($params);
        }

        $apiCall = self::API_URL . $function . $authMethod . (('GET' === $method) ? $paramString : null);

        // we want JSON
        $headerData = array('Accept: application/json');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiCall);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerData);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 90);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, true);

        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, count($params));
                curl_setopt($ch, CURLOPT_POSTFIELDS, ltrim($paramString, '&'));
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        $jsonData = curl_exec($ch);
        // split header from JSON data
        // and assign each to a variable
        list($headerContent, $jsonData) = explode("\r\n\r\n", $jsonData, 2);

        // convert header content into an array
        $headers = $this->processHeaders($headerContent);

        // get the 'X-Ratelimit-Remaining' header value
       // $this->_xRateLimitRemaining = $headers['X-Ratelimit-Remaining'];

        if (!$jsonData) {
            throw new InstagramException('Error: _makeCall() - cURL error: ' . curl_error($ch));
        }

        curl_close($ch);

        return json_decode($jsonData);
    }
    /**
     * Read and process response header content.
     *
     * @param array
     *
     * @return array
     */
    private function processHeaders($headerContent)
    {
        $headers = array();

        foreach (explode("\r\n", $headerContent) as $i => $line) {
            if ($i === 0) {
                $headers['http_code'] = $line;
                continue;
            }

            list($key, $value) = explode(':', $line);
            $headers[$key] = $value;
        }

        return $headers;
    }
}