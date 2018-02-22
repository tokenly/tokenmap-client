# Tokenmap Client

A tokenmap client library for Tokenly.

[![Build Status](https://travis-ci.org/tokenly/tokenmap-client.svg?branch=master)](https://travis-ci.org/tokenly/tokenmap-client)


# Installation

### Add the package via composer

```
composer require tokenly/tokenmap-client
```

## Usage with Laravel


### Set the environment variables

```
TOKENMAP_CONNECTION_URL=https://tokenmap.tokenly.com
```


### Simple BTC quote

Get a BTC quote in USD.  This will use bitcoinAverage and then fallback to Bitstamp if the data is not current

```php
$tokenmap_client = app('Tokenly\TokenmapClient\Client');
$usd_float = $tokenmap_client->getCurrentBTCQuoteWithFallback();
```


### Get a token quote

Get a token quote by going to BTC and then from BTC to USD.  This will use the default fallback sources of bitcoinAverage and bitstamp for the BTC quote.

```php
$tokenmap_client = app('Tokenly\TokenmapClient\Client');
$usd_float = $tokenmap_client->getTokenValue('poloniex', 'XCP');
```


## Get token information

```php
$tokenmap_client = app('Tokenly\TokenmapClient\Client');
$all_tokens = $tokenmap_client->allTokens();
```
