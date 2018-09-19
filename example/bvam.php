<?php

use Tokenly\TokenmapClient\Bvam\BVAMClient;

require __DIR__ . '/../vendor/autoload.php';

$tokenmap_connection_url = getenv('TOKENMAP_CONNECTION_URL');
if (!$tokenmap_connection_url) {
    $tokenmap_connection_url = 'https://tokenmap.tokenly.com';
}

$bvam_client = new BVAMClient($tokenmap_connection_url);


// 1 token
$token_info = $bvam_client->getAssetInfo('DEVON');
echo "\$token_info: " . json_encode($token_info, 192) . "\n";

// multiple tokens
$tokens = $bvam_client->getMultipleAssetsInfo(['DEVON', 'XFOO', 'SOUP']);
echo "\$tokens: " . json_encode($tokens, 192) . "\n";

// testnet token
$token_info = $bvam_client->getAssetInfo('TESTSOUP', 'counterpartyTestnet');
echo "\$token_info: " . json_encode($token_info, 192) . "\n";
