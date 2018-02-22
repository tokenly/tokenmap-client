<?php

// this is deprecated
//   Use MockeryBuilder instead

namespace Tokenly\TokenmapClient\Mock;

use Exception;
use Illuminate\Foundation\Application;
use Tokenly\TokenmapClient\Mock\MockTestCase;
use \PHPUnit_Framework_MockObject_MockBuilder;
use \PHPUnit_Framework_TestCase;

/**
* Tokenmap Mock Builder
* for Laravel apps
*/
class MockBuilder
{

    function __construct(Application $app) {
        $this->app = $app;

        $this->initMockRates();
    }
    

    ////////////////////////////////////////////////////////////////////////

    public function setMockRates($rate_entries) {
        foreach($this->makeRatesMap($rate_entries) as $rate_key => $new_rate_entry) {
            $this->mock_rates_map[$rate_key] = array_merge(isset($this->mock_rates_map[$rate_key]) ? $this->mock_rates_map[$rate_key] : [], $new_rate_entry);
        }
        return $this->mock_rates_map;
    }

    public function initMockRates() {
        $this->mock_rates_map = $this->makeRatesMap($this->getDefaultMockRates());
    }

    public function installTokenmapMockClient(PHPUnit_Framework_TestCase $test_case=null) {
        // record the calls
        $tokenmap_recorder = new \stdClass();
        $tokenmap_recorder->calls = [];

        if ($test_case === null) { $test_case = new MockTestCase(); }

        $tokenmap_client_mock = $test_case->getMockBuilder('\Tokenly\TokenmapClient\Client')
            ->disableOriginalConstructor()
            ->setMethods(['getQuote', 'loadQuote'])
            ->getMock();

        // override the getQuote method
        $tokenmap_client_mock->method('getQuote')->will($test_case->returnCallback(function($source, $pair) use ($tokenmap_recorder) {
            $pair_string = $pair[0].':'.$pair[1];

            // store the method for test verification
            $tokenmap_recorder->calls[] = [
                'method' => 'getQuote',
                'source' => $source,
                'pair'   => $pair_string,
            ];


            $rate_key = $source.'.'.$pair_string;
            if (!isset($this->mock_rates_map[$rate_key])) { throw new Exception("No mock rates defined for $source $pair_string", 1); }

            return $this->mock_rates_map[$rate_key];
        }));

        // override the loadQuote method
        $tokenmap_client_mock->method('loadQuote')->will($test_case->returnCallback(function($source, $pair) use ($tokenmap_recorder) {
            $pair_string = $pair[0].':'.$pair[1];

            // store the method for test verification
            $tokenmap_recorder->calls[] = [
                'method' => 'loadQuote',
                'source' => $source,
                'pair'   => $pair_string,
            ];


            $rate_key = $source.'.'.$pair_string;
            if (!isset($this->mock_rates_map[$rate_key])) { throw new Exception("No mock rates defined for $source $pair_string", 1); }

            return $this->mock_rates_map[$rate_key];
        }));


        // install the tokenmap client into the DI container
        $this->app->bind('Tokenly\TokenmapClient\Client', function($app) use ($tokenmap_client_mock) {
            return $tokenmap_client_mock;
        });


        // return an object to check the calls
        return $tokenmap_recorder;
    }

    ////////////////////////////////////////////////////////////////////////

    protected function makeRatesMap($rate_entries) {
        $map = [];
        foreach($rate_entries as $rate_entry) {
            $map[$rate_entry['source'].'.'.$rate_entry['pair']] = $rate_entry;
        }
        return $map;
    }

    protected function getDefaultMockRates() {
        return [
            [
                'source'     => 'bitcoinAverage',
                'pair'       => 'USD:BTC',
                'inSatoshis' => false,
                'bid'        => 199.95,
                'last'       => 200.00,
                'ask'        => 200.05,
                'bidLow'     => 190.00,
                'bidHigh'    => 210.00,
                'bidAvg'     => 200.00,
                'lastLow'    => 195.00,
                'lastHigh'   => 205.00,
                'lastAvg'    => 200.00,
                'askLow'     => 195.00,
                'askHigh'    => 205.00,
                'askAvg'     => 200.00,
                'start'      => '2015-06-04T07:00:00-0500',
                'end'        => '2015-06-05T07:00:00-0500',
                'time'       => '2015-06-05T07:00:00-0500',
            ],
            [
                'source'     => 'poloniex',
                'pair'       => 'BTC:MYTOKEN',
                'inSatoshis' => true,
                'bid'        => 95,
                'last'       => 100,
                'ask'        => 105,
                'bidLow'     => 95,
                'bidHigh'    => 105,
                'bidAvg'     => 100,
                'lastLow'    => 95,
                'lastHigh'   => 105,
                'lastAvg'    => 100,
                'askLow'     => 105,
                'askHigh'    => 105,
                'askAvg'     => 105,
                'start'      => '2015-06-04T07:00:00-0500',
                'end'        => '2015-06-05T07:00:00-0500',
                'time'       => '2015-06-05T07:00:00-0500',
            ],
        ];
}

}
