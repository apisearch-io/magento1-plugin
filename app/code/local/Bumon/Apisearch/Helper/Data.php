<?php
class Bumon_Apisearch_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Get api key
     *
     * @param string $storeCode
     * @return string
     */
    public function getApiKey($storeCode = null)
    {
        $storeCode = $storeCode === null ? Mage::app()->getStore() : $storeCode;
        return Mage::getStoreConfig('configuration/apisearch/apikey', $storeCode);
    }

    /* Get feed index key */
    public function getIndexKey($storeCode = null)
    {
        $storeCode = $storeCode === null ? Mage::app()->getStore() : $storeCode;
        return Mage::getStoreConfig('configuration/apisearch/indexkey', $storeCode);
    }

    /* Get feed index key */
    public function getTokenKey($storeCode = null)
    {
        $storeCode = $storeCode === null ? Mage::app()->getStore() : $storeCode;
        return Mage::getStoreConfig('configuration/apisearch/tokenkey', $storeCode);
    }
}
	 