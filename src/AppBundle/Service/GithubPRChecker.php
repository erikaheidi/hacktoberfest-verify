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
    protected $startDate;
    protected $endDate;

    /**
     * @param string $oauthtoken
     */
    public function __construct($oauthtoken)
    {
        $this->oauthToken = $oauthtoken;
        $this->guzzle = new Client();

        $this->startDate = new \DateTimeImmutable('first day of October');
        $this->endDate   = new \DateTimeImmutable('last day of October');
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
        $response = $this->authenticatedRequest('https://api.github.com/rate_limit');

        if ($response->getStatusCode() == 200) {
            $status = json_decode($response->getBody(), 1);

            return $status['resources']['core']['remaining'];
        }

        return 0;
    }

    /**
     * @param $username
     * @return array
     */
    public function getUserPullRequests($username)
    {
        $url = "https://api.github.com/users/$username/events";
        $pullrequests = [];
        $pages = [];

        // max 10 requests per user (max pagination allowed) = 300 results max.
        for ($i = 0; $i < 10; $i++) {
            $response = $this->authenticatedRequest($url);
            $headers = $response->getHeaders();

            if ($response->getStatusCode() == 200) {
                $pages[] = $this->getPullRequests($response);
            }

            if (!isset($headers['Link'])) {
                break;
            }

            $url = $this->getNextPage($headers['Link'][0]);

            if (!$url) {
                //no "next" page found; no more events for this user
                break;
            }
        }

        foreach ($pages as $page) {
            foreach ($page as $pr) {
                $pullrequests[] = $pr;
            }
        }

        return $pullrequests;
    }

    /**
     * Returns the next page to query
     * @param string $links
     * @return null
     */
    private function getNextPage($links)
    {
        $result = [];

        if (preg_match('#<(.*?)>; rel="next",#', $links, $result)) {
            return isset($result[1]) ? $result[1] : null;
        }

        return null;
    }

    /**
     * Returns an array with valid Pull Requests opened by this user within the right date range
     * @param ResponseInterface $response
     * @return array
     */
    private function getPullRequests(ResponseInterface $response)
    {
        $pullrequests = [];
        $events = json_decode($response->getBody(), 1);
        foreach ($events as $event) {
            if ($event['type'] !== 'PullRequestEvent') {
                continue;
            }

            $payload = $event['payload'];

            if ($payload['action'] !== 'opened') {
                continue;
            }

            //verify the date
            $created = new \DateTimeImmutable($payload['pull_request']['created_at']);
            if ( ($created < $this->startDate) or ($created > $this->endDate)) {
                continue;
            }

            $pullrequests[] = $payload['pull_request'];
        }

        return $pullrequests;
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
