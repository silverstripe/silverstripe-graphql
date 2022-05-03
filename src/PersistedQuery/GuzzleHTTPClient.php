<?php

namespace SilverStripe\GraphQL\PersistedQuery;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use RuntimeException;

class GuzzleHTTPClient implements HTTPClient
{
    /**
     * @param $url
     * @param int $timeout
     * @return null|string
     */
    public function getURL(string $url, int $timeout = 5): ?string
    {
        $request = new Request('GET', $url);
        $client = new Client();

        try {
            $response = $client->send($request, ['timeout' => $timeout]);
            $contents = trim($response->getBody()->getContents() ?? '');
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
