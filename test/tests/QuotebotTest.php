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
        $mockery_builder = new MockeryBuilder();
        $tokenmap_client = $mockery_builder->mockTokenmapClientWithRates();

        PHPUnit::assertEquals(4000, $tokenmap_client->getQuote('bitcoinAverage', ['USD','BTC'])['last']);
        PHPUnit::assertEquals(4000, $tokenmap_client->getQuote('bitcoinAverage', ['USD','BTC'])['last']);
        PHPUnit::assertEquals(4000, $tokenmap_client->getCurrentBTCQuoteWithFallback('USD', 'bitcoinAverage'));

        PHPUnit::assertEquals(4001, $tokenmap_client->getQuote('bitstamp', ['USD','BTC'])['last']);
        PHPUnit::assertEquals(4001, $tokenmap_client->getCurrentBTCQuoteWithFallback('USD', 'bitstamp'));
    }

    public function testGetRatesExpired() {
        $mockery_builder = new MockeryBuilder();
        $tokenmap_client = $mockery_builder->mockTokenmapClientWithRates();

        // make expired
        $tokenmap_client->_setNow(strtotime('2017-09-01T00:00:00-0500'));

        // should throw exception
        $this->expectException(ExpiredQuoteException::class);
        $tokenmap_client->getCurrentBTCQuoteWithFallback();
    }

    public function testGetFallback() {
        $mockery_builder = new MockeryBuilder();
        $tokenmap_client = $mockery_builder->mockTokenmapClientWithRates();

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
        $tokenmap_client = $mockery_builder->mockTokenmapClientWithRates();

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



}
