<?php

namespace Tokenly\TokenmapClient;

use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\all;
use Tokenly\TokenmapClient\Contracts\CacheStore;
use Tokenly\TokenmapClient\Exceptions\ExpiredQuoteException;

/**
 * Tokenmap Client
 */
class Client
{

    const SATOSHI = 100000000;

    protected $_now = null;

    public function __construct($tokenmap_url, CacheStore $cache_store)
    {
        $this->tokenmap_url = $tokenmap_url;
        $this->cache_store = $cache_store;
    }

    // ------------------------------------------------------------------------
    // quote functions

    /**
     * Converts a currency to a fiat value by going to BTC and then to fiat
     * @param  string     $source          The source for the currency quote (poloniex)
     * @param  array      $token           A token name like 'MYTOKEN'
     * @param  array|null $fiat_sources    An array of BTC quote sources in order. defaults to ['bitcoinAverage', 'bitstamp']
     * @param  array|null $fiat_pair       A fiat to BTC quote pair. defaults to ['USD','BTC']
     * @return float      A fiat amount
     */
    public function getTokenValue($source, $token, array $fiat_sources = null, array $fiat_pair = null)
    {
        $crypto_quote = $this->getQuote($source, ['BTC', $token]);

        if ($fiat_pair === null) {
            $fiat_pair = 'USD';
        }
        $fiat_value = $this->getCurrentBTCQuoteWithFallback($fiat_pair, $fiat_sources);

        $crypto_value = $crypto_quote['last'];

        if ($crypto_quote['inSatoshis']) {
            $crypto_value = $crypto_value / self::SATOSHI;
        }

        return $crypto_value * $fiat_value;
    }

    /**
     * gets a current price quote,
     * @param  array   $fiat A fiat currency to check market price - e.g USD, EUR
     * @param  array   $sources An array of BTC quote sources in order. defaults to ['bitcoinAverage', 'bitstamp']
     * @param  integer $stale_seconds The amount of time to consider a quote as expired. defaults to 3600 seconds (1 hour)
     * @throws ExpiredQuoteException if all sources are expired
     * @return float   A fiat amount
     */
    public function getCurrentBTCQuoteWithFallback($fiat = 'USD', $sources = null, $stale_seconds = null)
    {
        if ($sources === null) {$sources = ['bitcoinAverage', 'bitstamp'];}
        if ($stale_seconds === null) {$stale_seconds = 3600;}

        if (!is_array($sources) and $sources) {
            $sources = [$sources];
        }

        $quote_pair = [$fiat, 'BTC'];

        foreach ($sources as $source) {
            $quote = $this->getQuote($source, $quote_pair);
            if ($this->quoteIsFresh($quote, $stale_seconds)) {
                return $quote['last'];
            }
        }

        throw new ExpiredQuoteException("No sources were fresh.");
    }

    public function getQuote($source, array $pair)
    {
        $pair_string = $pair[0] . ':' . $pair[1];

        $cached_value = $this->cache_store->get($source . '.' . $pair_string);
        if ($cached_value !== null and $cached_value) {
            return $cached_value;
        }

        $quote = $this->loadQuote($source, $pair);
        return $quote;
    }

    public function loadQuote($source = null, array $pair = array())
    {
        $quote = null;
        $pair_string = null;
        if ($source !== null) {$pair_string = $pair[0] . ':' . $pair[1];}

        $loaded_quote_data = $this->loadRawQuotesData();
        foreach ($loaded_quote_data['quotes'] as $loaded_quote) {
            $loaded_source = $loaded_quote['source'];
            $loaded_pair_string = $loaded_quote['pair'];

            // cache for 10 minutes
            $this->cache_store->put($loaded_source . '.' . $loaded_pair_string, $loaded_quote, 10);

            if ($source !== null) {
                if ($loaded_source == $source and $loaded_pair_string == $pair_string) {
                    $quote = $loaded_quote;
                }
            }
        }

        if ($quote === null) {throw new Exception("Quote not found for $source with pair $pair_string", 1);}

        return $quote;
    }

    public function loadRawQuotesData()
    {
        return $this->loadFromAPI('quote/all');
    }

    // ------------------------------------------------------------------------
    // token information

    /**
     * Returns a numbered array of all tokens
     * Each token has:
     * {
     *   "chain": "bitcoin",
     *   "symbol": "BTC",
     *   "name": "Bitcoin",
     *   "asset": "BTC"
     * }
     * @return array All tokens
     */
    public function allTokens()
    {
        $raw_data = $this->allTokensByPage(0, 100);
        return $raw_data['items'];
    }

    /**
     * Returns single token information
     * Each token has:
     * {
     *   "chain": "bitcoin",
     *   "symbol": "BTC",
     *   "name": "Bitcoin",
     *   "asset": "BTC"
     * }
     * @return array Single token information
     */
    public function tokenInfoByChainAndSymbol($chain, $symbol)
    {
        return $this->loadFromAPI('token/' . $chain . '/' . $symbol . '');
    }

    /**
     * Returns single token information
     * Each token has:
     * {
     *   "chain": "bitcoin",
     *   "symbol": "BTC",
     *   "name": "Bitcoin",
     *   "asset": "BTC"
     * }
     * @return array Single token information
     */
    public function tokenInfoByChainAndAsset($chain, $asset)
    {
        return $this->loadFromAPI('asset/' . $chain . '/' . $asset . '');
    }

    // -----------------------------
    // Time handling
    //   only used for testing

    public function _setNow(int $now)
    {
        $this->_now = $now;
    }

    // ------------------------------------------------------------------------
    
    protected function allTokensByPage($pg, $limit) {
        $data = [
            'pg' => $pg,
            'limit' => $limit,
        ];

        $raw_data = $this->loadFromAPI('token/all', $data);
        return $raw_data;
    }

    // ------------------------------------------------------------------------

    protected function loadFromAPI($path = 'quote/all', $data = [])
    {
        $api_path = '/api/v1/' . $path;

        $client = new GuzzleClient();

        $request = new \GuzzleHttp\Psr7\Request('GET', $this->tokenmap_url . $api_path);
        $request = \GuzzleHttp\Psr7\modify_request($request, ['query' => http_build_query($data, null, '&', PHP_QUERY_RFC3986)]);

        // send request
        try {
            $response = $client->send($request);
        } catch (RequestException $e) {
            if ($response = $e->getResponse()) {
                // interpret the response and error message
                $code = $response->getStatusCode();
                try {
                    $json = json_decode($response->getBody(), true);
                } catch (Exception $parse_json_exception) {
                    // could not parse json
                    $json = null;
                }
                if ($json and isset($json['message'])) {
                    throw new Exception($json['message'], $code);
                }
            }

            // if no response, then just throw the original exception
            throw $e;
        }

        $code = $response->getStatusCode();
        if ($code == 204) {
            // empty content
            return [];
        }

        $json = json_decode($response->getBody(), true);
        if (!is_array($json)) {throw new Exception("Unexpected response", 1);}

        return $json;
    }

    protected function quoteIsFresh($quote, $stale_seconds)
    {
        $quote_ts = isset($quote['time']) ? strtotime($quote['time']) : 0;
        $now_ts = $this->getNow();

        if ($now_ts - $quote_ts >= $stale_seconds) {
            return false;
        }

        return true;
    }

    protected function getNow()
    {
        return isset($this->_now) ? $this->_now : time();
    }

}
