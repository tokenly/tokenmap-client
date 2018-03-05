<?php

namespace Tokenly\TokenmapClient;

use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\all;
use Tokenly\CryptoQuantity\CryptoQuantity;
use Tokenly\TokenmapClient\Contracts\CacheStore;
use Tokenly\TokenmapClient\Exceptions\ExpiredQuoteException;

/**
 * Tokenmap Client
 */
class TokenmapClient
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
     * Returns a simple quote as a
     * @param  string $currency  A base currency, like BTC or USD
     * @param  string $token     A token name like 'MYTOKEN'
     * @param  string $chain     A chain for this token, like bitcoin or ethereum
     * @param  int|null $stale_seconds Throw an error if the quote is more than this many seconds old.  This should be at least 120.
     * @param  boolean  $force_reload  If true, do not load from the cache
     * @return CryptoQuantity    A simple quantity representing the last traded price for this token
     */
    public function getSimpleQuote(string $currency, string $token, string $chain, int $stale_seconds = null, bool $force_reload = false)
    {
        $quote = $this->getQuote($currency, $token, $chain, $stale_seconds, $force_reload);
        return CryptoQuantity::fromFloat($quote['last']);
    }

    /**
     * Returns a simple quote as a
     * @param  string $currency        A base currency, like BTC or USD
     * @param  string $token           A token name like 'MYTOKEN'
     * @param  string $chain           A chain for this token, like bitcoin or ethereum
     * @param  int|null $stale_seconds Throw an error if the quote is more than this many seconds old.  This should be at least 120.
     * @param  boolean  $force_reload  If true, do not load from the cache
     * @return array  Full quote data.  See https://tokenmap-stage.tokenly.com/api/v1/quote/bitcoin/USD:BTC for an example
     */
    public function getQuote(string $currency, string $token, string $chain, int $stale_seconds = null, bool $force_reload = false)
    {
        $api_path = "quote/{$chain}/{$currency}:{$token}";

        $cached_data = $force_reload ? null : $this->cache_store->get($api_path);
        if ($cached_data !== null and $cached_data) {
            $quote = $cached_data;
        } else {
            $quote = $this->loadQuoteFromAPI($currency, $token, $chain);
        }

        if ($stale_seconds !== null) {
            if (!$this->quoteIsFresh($quote, $stale_seconds)) {
                throw new ExpiredQuoteException("The requested quote was not fresh.");
            }
        }

        return $quote;
    }

    public function loadAllQuotesData()
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
    public function allTokens($with_cache = true)
    {
        if ($with_cache) {
            $cached_data = $this->cache_store->get('tokenmap.allTokens');
            if ($cached_data !== null and $cached_data) {
                return $cached_data;
            }
        }

        $raw_data = $this->allTokensByPage(0, 100);
        $loaded_data = $raw_data['items'];

        if ($with_cache) {
            // cache for 2 minutes
            $this->cache_store->put('tokenmap.allTokens', $loaded_data, 2);
        }

        return $loaded_data;
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
    public function tokenInfoByChainAndSymbol($chain, $symbol, $with_cache = true)
    {
        $cache_key = $with_cache ? 'tokenmap.bySymbol.' . $chain . '.' . $symbol : null;
        return $this->loadTokenUsingCache($cache_key, function () use ($chain, $symbol) {
            return $this->loadTokenInfromFromApiByChainAndSymbol($chain, $symbol);
        });
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
    public function tokenInfoByChainAndAsset($chain, $asset, $with_cache = true)
    {
        $cache_key = $with_cache ? 'tokenmap.byAsset.' . $chain . '.' . $asset : null;
        return $this->loadTokenUsingCache($cache_key, function () use ($chain, $asset) {
            return $this->loadTokenInfromFromApiByChainAndAsset($chain, $asset);
        });
    }

    // -----------------------------
    // Time handling
    //   only used for testing

    public function _setNow(int $now)
    {
        $this->_now = $now;
    }

    // ------------------------------------------------------------------------

    protected function allTokensByPage($pg, $limit)
    {
        $data = [
            'pg' => $pg,
            'limit' => $limit,
        ];

        $raw_data = $this->loadFromAPI('token/all', $data);
        return $raw_data;
    }

    protected function loadTokenUsingCache($cache_key, $fetch_callback_fn)
    {
        if ($cache_key != null) {
            // try cache first
            $cached_data = $this->cache_store->get($cache_key);
            if ($cached_data !== null) {
                // if we stored a false in the cache, that means the token wasn't found
                if ($cached_data === false) {
                    return null;
                }
                return $cached_data;
            }
        }

        try {
            $loaded_data = $fetch_callback_fn();
        } catch (RequestException $e) {
            // catch a 404 and treat it as not found
            if ($e->getCode() == 404) {
                // be sure to store a false here
                $loaded_data = false;
            } else {
                // other errors are not cached
                throw $e;
            }
        }

        if ($cache_key != null) {
            // cache for 2 minutes
            $this->cache_store->put($cache_key, $loaded_data, 2);
        }

        return $loaded_data;
    }

    protected function loadTokenInfromFromApiByChainAndSymbol($chain, $symbol)
    {
        return $this->loadFromAPI('token/' . $chain . '/' . $symbol . '');
    }

    protected function loadTokenInfromFromApiByChainAndAsset($chain, $asset)
    {
        return $this->loadFromAPI('asset/' . $chain . '/' . $asset . '');
    }

    // ------------------------------------------------------------------------

    protected function loadQuoteFromAPI(string $currency, string $token, string $chain)
    {
        $quote = null;

        // load from a URL like /api/v1/quote/bitcoin/USD:BTC
        $api_path = "quote/{$chain}/{$currency}:{$token}";
        $quote = $this->loadFromAPI("quote/{$chain}/{$currency}:{$token}");

        // cache for 1 minute
        $this->cache_store->put($api_path, $quote, 1);

        if ($quote === null) {
            throw new Exception("Quote not found for {$currency}:{$token} on chain $chain", 1);
        }

        return $quote;
    }

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
