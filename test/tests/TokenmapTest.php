<?php

use PHPUnit_Framework_Assert as PHPUnit;
use Tokenly\TokenmapClient\Exceptions\ExpiredQuoteException;
use Tokenly\TokenmapClient\Mock\MockeryBuilder;

/*
 *
 */
class TokenmapTest extends PHPUnit_Framework_TestCase
{

    public function testGetQuote()
    {
        $tokenmap_client = MockeryBuilder::buildTokenmapClientMock();

        PHPUnit::assertEquals(4000, $tokenmap_client->getSimpleQuote('USD', 'BTC', 'bitcoin')->getFloatValue());
        PHPUnit::assertEquals(100, $tokenmap_client->getSimpleQuote('BTC', 'MYTOKEN', 'bitcoin')->getFloatValue());
    }

    public function testGetRatesExpired()
    {
        $tokenmap_client = MockeryBuilder::buildTokenmapClientMock();

        // make expired
        $tokenmap_client->_setNow(strtotime('2017-09-01T00:00:00-0500'));

        // should throw exception
        $this->expectException(ExpiredQuoteException::class);
        $tokenmap_client->getSimpleQuote('USD', 'BTC', 'bitcoin', 3600);
    }

    public function testGetTokens()
    {
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

    public function testCacheNotFoundToken()
    {
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
