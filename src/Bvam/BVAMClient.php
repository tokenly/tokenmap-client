<?php

namespace Tokenly\TokenmapClient\Bvam;

use Exception;
use Tokenly\APIClient\TokenlyAPI;

class BVAMClient extends TokenlyAPI
{

    public function getAssetInfo(string $asset_name, string $chain = null)
    {
        if ($chain === null) {
            $chain = 'counterparty';
        }
        return $this->getPublic("api/v1/bvam/{$chain}/asset/{$asset_name}");
    }

    public function getMultipleAssetsInfo(array $asset_names, string $chain = null)
    {
        if ($chain === null) {
            $chain = 'counterparty';
        }
        $api_result = $this->getPublic("api/v1/bvam/{$chain}/assets", ['assets' => implode(',', $asset_names)]);
        if (!$api_result) {
            return null;
        }

        // use asset names as array keys
        $output = array();
        foreach ($api_result as $asset_entry) {
            $output[$asset_entry['asset']] = $asset_entry;
        }
        return $output;
    }

    // ------------------------------------------------------------------------
    // Deprecated methods

    public function getBvamList()
    {
        throw new Exception("Deprecated method getBvamList is not supported by Tokenmap", 1);
    }
    public function getCategoryList()
    {
        throw new Exception("Deprecated method getCategoryList is not supported by Tokenmap", 1);
    }
    public function addBvamJson()
    {
        throw new Exception("Deprecated method addBvamJson is not supported by Tokenmap", 1);
    }
    public function addCategoryJson()
    {
        throw new Exception("Deprecated method addCategoryJson is not supported by Tokenmap", 1);
    }

}
