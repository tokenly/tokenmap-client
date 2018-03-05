<?php

namespace Tokenly\TokenmapClient\Mock;

use GuzzleHttp\Exception\RequestException;
use Mockery;
use Tokenly\TokenmapClient\Mock\MemoryCacheStore;
use Tokenly\TokenmapClient\TokenmapClient;

/**
 * Tokenmap Mockery Builder
 */
class MockeryBuilder
{

    protected $rate_entries;
    protected $tokens_list;
    protected $memory_cache_store;
    protected $tokenmap_client_mock;

    // binds into the Laravel application container
    public static function bindTokenmapClientMock()
    {
        $builder = new MockeryBuilder();
        app()->instance(TokenmapClient::class, $builder->buildMock());
        return $builder;
    }

    // just returns the mocked client
    public static function buildTokenmapClientMock()
    {
        $builder = new MockeryBuilder();
        return $builder->buildMock();
    }

    public function __construct()
    {
        $this->rate_entries = $this->getDefaultMockRateEntries();
        $this->tokens_list = $this->getDefaultTokensList();
    }

    ////////////////////////////////////////////////////////////////////////

    public function getMock()
    {
        return $this->tokenmap_client_mock;
    }

    public function buildMock()
    {
        $this->tokenmap_client_mock = Mockery::mock(TokenmapClient::class, ['http://127.0.0.1/', $this->getMemoryCacheStore()])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->tokenmap_client_mock->shouldReceive('loadQuoteFromAPI')->andReturnUsing(function (string $currency, string $token, string $chain) {
            return $this->rate_entries["{$currency}:{$token}:{$chain}"];
        });

        // tokenslist
        $this->tokenmap_client_mock->shouldReceive('allTokensByPage')->andReturnUsing(function () {
            return $this->getTokensListResponse();
        });

        // by chain
        $this->tokenmap_client_mock->shouldReceive('loadTokenInfromFromApiByChainAndSymbol')->andReturnUsing(function ($chain, $symbol) {
            foreach ($this->tokens_list as $entry) {
                if ($entry['chain'] == $chain and $entry['symbol'] == $symbol) {
                    return $entry;
                }
            }

            // mock 404 response exception
            $request = new \GuzzleHttp\Psr7\Request('GET', 'http://foo.com/api/token/foo');
            $response = new \GuzzleHttp\Psr7\Response(404);
            throw new RequestException("Token Not Found", $request, $response);
        });

        // by asset
        $this->tokenmap_client_mock->shouldReceive('loadTokenInfromFromApiByChainAndAsset')->andReturnUsing(function ($chain, $asset) {
            foreach ($this->tokens_list as $entry) {
                if ($entry['chain'] == $chain and $entry['asset'] == $asset) {
                    return $entry;
                }
            }

            // mock 404 response exception
            $request = new \GuzzleHttp\Psr7\Request('GET', 'http://foo.com/api/token/foo');
            $response = new \GuzzleHttp\Psr7\Response(404);
            throw new RequestException("Token Not Found", $request, $response);
        });

        // set fresh (1 min old) by default
        $this->tokenmap_client_mock->_setNow(strtotime('2017-08-30T00:01:00-0500'));

        return $this->tokenmap_client_mock;
    }

    public function setMockRateEntries($rate_entries)
    {
        $this->rate_entries = $rate_entries;
        return $this;
    }

    public function setMockTokensList($tokens_list)
    {
        $this->tokens_list = $tokens_list;
        return $this;
    }

    public function getMemoryCacheStore()
    {
        if (!isset($this->memory_cache_store)) {
            $this->memory_cache_store = new MemoryCacheStore();
        }
        return $this->memory_cache_store;
    }

    public function getDefaultTokensList()
    {
        return [
            [
                'chain' => 'bitcoin',
                'symbol' => 'BTC',
                'name' => 'Bitcoin',
                'asset' => 'BTC',
            ],
            [
                'chain' => 'ethereum',
                'symbol' => 'ETH',
                'name' => 'Ethereum',
                'asset' => 'ETH',
            ],
            [
                'chain' => 'bitcoin',
                'symbol' => 'XCP',
                'name' => 'Counterparty',
                'asset' => 'XCP',
            ],
            [
                'chain' => 'bitcoin',
                'symbol' => 'FLDC',
                'name' => 'Foldincoin',
                'asset' => 'FLDC',
            ],
            [
                'chain' => 'bitcoin',
                'symbol' => 'BCY',
                'name' => 'Bitcrystals',
                'asset' => 'BCY',
            ],
            [
                'chain' => 'ethereum',
                'symbol' => 'CAT',
                'name' => 'Cat Token',
                'asset' => '0x06012c8cf97BEaD5deAe237070F9587f8E7A266d',
            ],
        ];
    }

    public function getTokensListResponse()
    {
        return [
            'page' => 0,
            'perPage' => 50,
            'pageCount' => 1,
            'count' => count($this->tokens_list),
            'items' => $this->tokens_list,
        ];
    }

    public function getDefaultMockRateEntries()
    {
        return [
            'USD:BTC:bitcoin' => [
                'source' => 'bitcoinAverage',
                'pair' => 'USD:BTC',
                'inSatoshis' => false,
                'bid' => 3999.95,
                'last' => 4000.00,
                'ask' => 4000.05,
                'bidLow' => 3999.00,
                'bidHigh' => 4005.00,
                'bidAvg' => 4000.00,
                'lastLow' => 3999.00,
                'lastHigh' => 4005.00,
                'lastAvg' => 4000.00,
                'askLow' => 3999.00,
                'askHigh' => 4005.00,
                'askAvg' => 4000.00,
                'start' => '2017-08-30T00:00:00-0500',
                'end' => '2017-08-30T00:00:00-0500',
                'time' => '2017-08-30T00:00:00-0500',
            ],
            'BTC:MYTOKEN:bitcoin' => [
                'source' => 'poloniex',
                'pair' => 'BTC:MYTOKEN',
                'inSatoshis' => true,
                'bid' => 95,
                'last' => 100,
                'ask' => 105,
                'bidLow' => 95,
                'bidHigh' => 105,
                'bidAvg' => 100,
                'lastLow' => 95,
                'lastHigh' => 105,
                'lastAvg' => 100,
                'askLow' => 105,
                'askHigh' => 105,
                'askAvg' => 105,
                'start' => '2017-08-30T00:00:00-0500',
                'end' => '2017-08-30T00:00:00-0500',
                'time' => '2017-08-30T00:00:00-0500',
            ],
        ];
    }

    // ------------------------------------------------------------------------

}
