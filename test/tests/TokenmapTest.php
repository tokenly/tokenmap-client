<?php

use PHPUnit_Framework_Assert as PHPUnit;
use Tokenly\TokenmapClient\Exceptions\ExpiredQuoteException;
use Tokenly\TokenmapClient\Mock\MockeryBuilder;

/*
* 
*/
class TokenmapTest extends PHPUnit_Framework_TestCase
{


    public function testGetRates() {
        $tokenmap_client = MockeryBuilder::buildTokenmapClientMock();

        PHPUnit::assertEquals(4000, $tokenmap_client->getQuote('bitcoinAverage', ['USD','BTC'])['last']);
        PHPUnit::assertEquals(4000, $tokenmap_client->getQuote('bitcoinAverage', ['USD','BTC'])['last']);
        PHPUnit::assertEquals(4000, $tokenmap_client->getCurrentBTCQuoteWithFallback('USD', 'bitcoinAverage'));

        PHPUnit::assertEquals(4001, $tokenmap_client->getQuote('bitstamp', ['USD','BTC'])['last']);
        PHPUnit::assertEquals(4001, $tokenmap_client->getCurrentBTCQuoteWithFallback('USD', 'bitstamp'));
    }

    public function testGetRatesExpired() {
        $tokenmap_client = MockeryBuilder::buildTokenmapClientMock();

        // make expired
        $tokenmap_client->_setNow(strtotime('2017-09-01T00:00:00-0500'));

        // should throw exception
        $this->expectException(ExpiredQuoteException::class);
        $tokenmap_client->getCurrentBTCQuoteWithFallback();
    }

    public function testGetFallback() {
        $mockery_builder = new MockeryBuilder();
        $tokenmap_client = $mockery_builder->buildMock();

        PHPUnit::assertEquals(4000, $tokenmap_client->getCurrentBTCQuoteWithFallback());

        // set bitcoinAverage quote as stale
        $entries = $mockery_builder->getDefaultMockRateEntries();
        $entries[0]['time'] = '2017-08-28T00:00:00-0500';
        $mockery_builder->setMockRateEntries($entries);

        // clear cache so entries are reloaded
        $mockery_builder->getMemoryCacheStore()->clear();

        // fallback should kick in now
        PHPUnit::assertEquals(4001, $tokenmap_client->getCurrentBTCQuoteWithFallback());
    }


    public function testCurrencyQuoteWithFallback() {
        $mockery_builder = new MockeryBuilder();
        $tokenmap_client = $mockery_builder->buildMock();

        // simple quote
        PHPUnit::assertEquals(0.004000, $tokenmap_client->getTokenValue('poloniex', 'MYTOKEN'));


        // set bitcoinAverage quote as stale
        $entries = $mockery_builder->getDefaultMockRateEntries();
        $entries[0]['time'] = '2017-08-28T00:00:00-0500';
        $mockery_builder->setMockRateEntries($entries);

        // clear cache so entries are reloaded
        $mockery_builder->getMemoryCacheStore()->clear();

        // fallback should kick in now
        PHPUnit::assertEquals(0.004001, $tokenmap_client->getTokenValue('poloniex', 'MYTOKEN'));
    }


    public function testGetTokens() {
        $mockery_builder = new MockeryBuilder();
        $tokenmap_client = $mockery_builder->buildMock();

        $expected_tokens_list = $mockery_builder->getDefaultTokensList();

        // all tokens
        PHPUnit::assertEquals($expected_tokens_list, $tokenmap_client->allTokens());

        // token by symbol
        PHPUnit::assertEquals($expected_tokens_list[3], $tokenmap_client->tokenInfoByChainAndSymbol('bitcoin', 'FLDC'));

        // token by asset
        PHPUnit::assertEquals($expected_tokens_list[5], $tokenmap_client->tokenInfoByChainAndAsset('ethereum', '0x06012c8cf97BEaD5deAe237070F9587f8E7A266d'));

        // check cached data
        $cache_key = 'tokenmap.bySymbol.bitcoin.FLDC';
        $cached_data = $mockery_builder->getMemoryCacheStore()->get($cache_key);
        PHPUnit::assertEquals($expected_tokens_list[3], $cached_data);
        $cache_key = 'tokenmap.byAsset.ethereum.0x06012c8cf97BEaD5deAe237070F9587f8E7A266d';
        $cached_data = $mockery_builder->getMemoryCacheStore()->get($cache_key);
        PHPUnit::assertEquals($expected_tokens_list[5], $cached_data);

    }

    public function testCacheNotFoundToken() {
        $mockery_builder = new MockeryBuilder();
        $tokenmap_client = $mockery_builder->buildMock();

        $expected_tokens_list = $mockery_builder->getDefaultTokensList();

        // token by symbol
        PHPUnit::assertEquals(null, $tokenmap_client->tokenInfoByChainAndSymbol('bitcoin', 'NOTFOUND'));

        // check cached data
        $cache_key = 'tokenmap.bySymbol.bitcoin.NOTFOUND';
        $cached_data = $mockery_builder->getMemoryCacheStore()->get($cache_key);
        PHPUnit::assertTrue(false === $cached_data);

        // token by asset
        PHPUnit::assertEquals(null, $tokenmap_client->tokenInfoByChainAndAsset('bitcoin', 'NOTFOUNDASSET'));

        // check cached data
        $cache_key = 'tokenmap.byAsset.bitcoin.NOTFOUNDASSET';
        $cached_data = $mockery_builder->getMemoryCacheStore()->get($cache_key);
        PHPUnit::assertTrue(false === $cached_data);

    }



}
