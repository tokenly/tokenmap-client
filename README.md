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

Get a BTC quote in USD.  This will use the sources as defined in the Tokenmap interface.

```php
$tokenmap_client = app('Tokenly\TokenmapClient\TokenmapClient');
$usd_float = $tokenmap_client->getSimpleQuote('USD', 'BTC', 'bitcoin')->getFloatValue();
```


## Get token information

```php
$tokenmap_client = app('Tokenly\TokenmapClient\TokenmapClient');
$all_tokens = $tokenmap_client->allTokens();
```
