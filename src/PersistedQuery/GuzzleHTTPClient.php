<?php

namespace SilverStripe\GraphQL\PersistedQuery;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

class GuzzleHTTPClient implements HTTPClient
{
    /**
     * @param $url
     * @param int $timeout
     * @return null|string
     */
    public function getURL($url, $timeout = 5)
    {
        $request = new Request('GET', $url);
        $client = new Client();

        try {
            $response = $client->send($request, ['timeout' => $timeout]);
            $contents = trim($response->getBody()->getContents());
        } catch (RuntimeException $e) {
            user_error($e->getMessage(), E_USER_WARNING);
            return null;
        } catch (GuzzleException $e) {
            user_error($e->getMessage(), E_USER_WARNING);
            return null;
        }

        return $contents;
    }
}
