<?php

namespace Tokenly\TokenmapClient\ServiceProvider;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

/*
* TokenmapServiceProvider
*/
class TokenmapServiceProvider extends ServiceProvider
{

    public function boot()
    {
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('Tokenly\TokenmapClient\Client', function($app) {
            $cache_store = app('Tokenly\TokenmapClient\LaravelCacheStore\LaravelCacheStore');

            $tokenmap_connection_url = env('TOKENMAP_CONNECTION_URL', 'https://tokenmap.tokenly.com');

            $tokenmap_client = new \Tokenly\TokenmapClient\Client($tokenmap_connection_url, $cache_store);
            return $tokenmap_client;
        });
    }

}

