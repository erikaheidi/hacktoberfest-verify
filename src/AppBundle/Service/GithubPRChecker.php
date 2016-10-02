<?php
/**
 * PR Check service
 */

namespace AppBundle\Service;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class GithubPRChecker
{
    protected $oauthToken;
    protected $guzzle;

    static $API_BASE = 'https://api.github.com';

    /**
     * @param string $oauthtoken
     */
    public function __construct($oauthtoken)
    {
        $this->oauthToken = $oauthtoken;
        $this->guzzle = new Client();
    }

    /**
     * @param string $oauthToken
     */
    public function setOauthToken($oauthToken)
    {
        $this->oauthToken = $oauthToken;
    }

    /**
     * Checks the API limits and returns the remaining calls
     * @return int
     */
    public function getRemainingCalls()
    {
        $response = $this->authenticatedRequest(self::$API_BASE . '/rate_limit');

        if ($response->getStatusCode() == 200) {
            $status = json_decode($response->getBody(), 1);

            return $status['resources']['core']['remaining'];
        }

        return 0;
    }

    /**
     * @param string $username
     * @param bool $lazy When set to true, will return only the raw json decoded search result, not fetching individual PRs
     * @return array
     */
    public function getUserPullRequests($username, $lazy = false)
    {
        $url = self::$API_BASE . "/search/issues?q=author:$username+type:pr+created:2016-09-30T00:00:00-12:00..2016-10-31T23:59:59-12:00";

        $response = $this->authenticatedRequest($url);
        $pullrequests = [];

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        $body = json_decode($response->getBody(), 1);
        $total = $body['total_count'];
        if ($total == 0) {
            return null;
        }

        if ($lazy) {
            return $body;
        }

        //fetches individual PRs with more detailed info including Project
        foreach ($body['items'] as $event) {
            $prinfo = $this->authenticatedRequest($event['pull_request']['url']);
            $pullrequests[] = json_decode($prinfo->getBody(), 1);
        }

        return ['total_count' => $total, 'items' => $pullrequests ];
    }
    /**
     * @param string $endpoint
     * @return ResponseInterface
     */
    private function authenticatedRequest($endpoint)
    {
        $params = [
            'headers' => [
                'Authorization' => 'token ' . $this->oauthToken
            ]
        ];

        return $this->guzzle->get($endpoint, $params);
    }
}
