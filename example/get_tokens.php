<?php

use Tokenly\TokenmapClient\TokenmapClient;
use Tokenly\TokenmapClient\Mock\MemoryCacheStore;

require __DIR__ . '/../vendor/autoload.php';

$tokenmap_connection_url = getenv('TOKENMAP_CONNECTION_URL');
if (!$tokenmap_connection_url) {
    $tokenmap_connection_url = 'https://tokenmap.tokenly.com';
}

$tokenmap_client = new TokenmapClient($tokenmap_connection_url, new MemoryCacheStore());


// all tokens
$all_tokens = $tokenmap_client->allTokens();
echo "\$all_tokens: " . json_encode($all_tokens, 192) . "\n";


// by symbol
$token = $tokenmap_client->tokenInfoByChainAndSymbol('bitcoin', 'BTC');
echo "\$token: ".json_encode($token, 192)."\n";


// by asset
$token = $tokenmap_client->tokenInfoByChainAndAsset('bitcoin', 'BTC');
echo "\$token: ".json_encode($token, 192)."\n";


